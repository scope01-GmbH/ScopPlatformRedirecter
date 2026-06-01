<?php
declare(strict_types=1);
/**
 * Implemented by scope01 GmbH team https://scope01.com
 *
 * @copyright scope01 GmbH https://scope01.com
 * @license MIT
 * @link https://scope01.com
 */

namespace Scop\PlatformRedirecter\Subscriber;

use Scop\PlatformRedirecter\Redirect\RedirectDefinition;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Store\InAppPurchase;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Write\Validation\PreWriteValidationEvent;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Framework\Validation\WriteConstraintViolationException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Contracts\Translation\TranslatorInterface;

class RedirectValidationSubscriber implements EventSubscriberInterface
{

    private const IN_APP_PURCHASE_ID = 'scopPlatformRedirecterPremium';

    private Context $context;
    public function __construct(
        readonly private ValidatorInterface $validator,
        readonly private TranslatorInterface $translator,
        readonly private EntityRepository   $salesChannelRepository,
        readonly private InAppPurchase      $inAppPurchase,
    )
    {}

    public static function getSubscribedEvents(): array
    {
        return [
            PreWriteValidationEvent::class => 'onPreWriteValidation',
        ];
    }

    /**
     * @param PreWriteValidationEvent $event
     * @return void
     */
    public function onPreWriteValidation(PreWriteValidationEvent $event): void
    {
        $writeException = $event->getExceptions();
        $commands = $event->getCommands();
        $violationList = new ConstraintViolationList();
        $this->context = $event->getContext();
        foreach ($commands as $command) {
            if ($command->getEntityName() !== RedirectDefinition::ENTITY_NAME || !method_exists($command, 'getPayload')) {
                continue;
            }
            $payload = $command->getPayload();

            // Entity-link feature is gated behind the Premium IAP. Block writes that try to set the
            // entity-link columns when the IAP is not active.
            $writesEntityLink = !empty($payload['target_entity_type']) || !empty($payload['target_entity_id']);
            if ($writesEntityLink && !$this->inAppPurchase->isActive('ScopPlatformRedirecter', self::IN_APP_PURCHASE_ID)) {
                $violationList->add(new ConstraintViolation(
                    $this->translator->trans('Scop.PlatformRedirecter.validation.entityLinkRequiresIap'),
                    null,
                    [],
                    '',
                    rtrim(str_replace('[', '/', $command->getPath()), ']') . '/targetEntityType',
                    $payload['target_entity_type'] ?? null,
                    null,
                    null,
                    null
                ));
                $writeException->add(new WriteConstraintViolationException($violationList));
                continue;
            }

            $violations = $this->validator->startContext()->atPath($command->getPath())->validate($payload, $this->getConstraints($payload))->getViolations();
            if ($violations->count() > 0) {
                foreach ($violations as $v) {
                    $violationList->add(new ConstraintViolation(
                        $v->getMessage(),
                        $v->getMessageTemplate(),
                        $v->getParameters(),
                        '',
                        rtrim(str_replace('[', '/', $v->getPropertyPath()), ']'),
                        $v->getInvalidValue(),
                        $v->getPlural(),
                        null,
                        $v->getConstraint()
                    ));
                }
                $writeException->add(new WriteConstraintViolationException($violationList));
            }
        }
    }

    /**
     * @param array<string, mixed> $payload
     * @return Assert\Collection
     */
    public function getConstraints(array $payload = []): Assert\Collection
    {
        $hasEntityLink = !empty($payload['target_entity_type']) && !empty($payload['target_entity_id']);

        $targetUrlConstraints = [new Assert\Type('string')];
        if (!$hasEntityLink) {
            $targetUrlConstraints[] = new Assert\NotBlank();
        }

        return new Assert\Collection([
            'fields' => [
                'id' => [
                    new Assert\NotBlank(),
                    new Assert\Callback([$this, 'validateId']),
                ],
                'httpCode' => [
                    new Assert\NotBlank(),
                    new Assert\Choice(choices: [301, 302], message: $this->translator->trans('Scop.PlatformRedirecter.validation.choice'))
                ],
                'sourceURL' => [
                    new Assert\NotBlank(),
                    new Assert\Type('string'),
                    new Assert\NotEqualTo('/', message: $this->translator->trans('Scop.PlatformRedirecter.validation.homeSourceUrl')),
                    new Assert\Regex(pattern: '/^(?!\/admin).*$/', message: $this->translator->trans('Scop.PlatformRedirecter.validation.notBeginWith', ['%forbidden%' => '/admin'])),
                    new Assert\Regex(pattern: '/^(?!\/api).*$/', message: $this->translator->trans('Scop.PlatformRedirecter.validation.notBeginWith', ['%forbidden%' => '/api'])),
                    new Assert\Regex(pattern: '/^(?!\/widgets).*$/', message: $this->translator->trans('Scop.PlatformRedirecter.validation.notBeginWith', ['%forbidden%' => '/widgets'])),
                    new Assert\Regex(pattern: '/^(?!\/store-api).*$/', message: $this->translator->trans('Scop.PlatformRedirecter.validation.notBeginWith', ['%forbidden%' => '/store-api'])),
                    new Assert\Regex(pattern: '/^(?!\/_profiler).*$/', message: $this->translator->trans('Scop.PlatformRedirecter.validation.notBeginWith', ['%forbidden%' => '/_profiler']))
                ],
                'targetURL' => $targetUrlConstraints,
                'enabled' => [
                    new Assert\NotBlank(),
                    new Assert\Choice(choices:  [0, 1, false, true,], message: $this->translator->trans('Scop.PlatformRedirecter.validation.choice'))
                ],
                'queryParamsHandling' =>  [
                    new Assert\Choice(choices:  [0, 1, 2], message: $this->translator->trans('Scop.PlatformRedirecter.validation.choice'))
                ],
                'salesChannelId' => [
                    new Assert\Callback([$this, 'validateId']),
                    new Assert\Callback([$this, 'validateSaleChannelId']),
                ]
            ],
            'allowExtraFields' => true,
            'allowMissingFields' => true,
        ]);
    }

    /**
     * @param string|null $id
     * @param ExecutionContextInterface $assertContext
     * @return void
     */
    public function validateSaleChannelId(?string $id, ExecutionContextInterface $assertContext): void
    {
        if ($id === null) {
            return;
        }

        $salesChannelsIds = $this->salesChannelRepository->searchIds(new Criteria(), $this->context)->getIds();
        if (!in_array(strtolower($id), $salesChannelsIds, true) && !in_array(Uuid::fromBytesToHex($id), $salesChannelsIds, true)) {
            $assertContext->buildViolation('Sales channel is not exist')
                ->addViolation();
        }
    }

    /**
     * @param string|null $id
     * @param ExecutionContextInterface $assertContext
     * @return void
     */
    public function validateId(?string $id, ExecutionContextInterface $assertContext): void
    {
        if ($id !== null && !Uuid::isValid($id) && !Uuid::isValid(Uuid::fromBytesToHex($id))) {
            $assertContext->buildViolation('This is not a valid UUID: ' . $id)
                ->addViolation();
        }
    }
}