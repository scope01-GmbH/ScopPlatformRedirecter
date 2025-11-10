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
use Shopware\Core\Framework\Adapter\Translation\AbstractTranslator;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
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

class RedirectValidationSubscriber implements EventSubscriberInterface
{

    private Context $context;
    public function __construct(
        readonly private ValidatorInterface $validator,
        readonly private AbstractTranslator $translator,
        readonly private EntityRepository   $salesChannelRepository,
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
            $violations = $this->validator->startContext()->atPath($command->getPath())->validate($payload, $this->getConstraints())->getViolations();
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
     * @return Assert\Collection
     */
    public function getConstraints(): Assert\Collection
    {
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
                ],
                'targetURL' => [
                    new Assert\NotBlank(),
                    new Assert\Type('string'),
                ],
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
     * @param string $id
     * @param ExecutionContextInterface $assertContext
     * @return void
     */
    public function validateSaleChannelId(string $id, ExecutionContextInterface $assertContext): void
    {
        $salesChannelsIds = $this->salesChannelRepository->searchIds(new Criteria(), $this->context)->getIds();
        if (!in_array(strtolower($id), $salesChannelsIds, true) && !in_array(Uuid::fromBytesToHex($id), $salesChannelsIds, true)) {
            $assertContext->buildViolation('Sales channel is not exist')
                ->addViolation();
        }
    }

    /**
     * @param string $id
     * @param ExecutionContextInterface $assertContext
     * @return void
     */
    public function validateId(string $id, ExecutionContextInterface $assertContext): void
    {
        if (!Uuid::isValid($id) && !Uuid::isValid(Uuid::fromBytesToHex($id))) {
            $assertContext->buildViolation('This is not a valid UUID: ' . $id)
                ->addViolation();
        }
    }
}