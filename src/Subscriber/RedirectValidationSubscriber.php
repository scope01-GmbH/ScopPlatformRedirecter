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
use Shopware\Core\Framework\DataAbstractionLayer\Write\Validation\PreWriteValidationEvent;
use Shopware\Core\Framework\Validation\WriteConstraintViolationException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints as Assert;

readonly class RedirectValidationSubscriber implements EventSubscriberInterface
{

    public function __construct(private ValidatorInterface $validator, private AbstractTranslator $translator)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PreWriteValidationEvent::class => 'onPreWriteValidation',
        ];
    }

    public function onPreWriteValidation(PreWriteValidationEvent $event): void
    {
        $writeException = $event->getExceptions();
        $commands = $event->getCommands();
        $violationList = new ConstraintViolationList();

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

    public function getConstraints(): Assert\Collection
    {
        return new Assert\Collection([
            'fields' => [
                'httpCode' => [
                    new Assert\NotBlank(),
                    new Assert\Choice(choices: [301, 302], message: $this->translator->trans('Scop.PlatformRedirecter.validation.choice'))
                ],
                'sourceURL' => [
                    new Assert\NotBlank(),
                ],
                'targetURL' => [
                    new Assert\NotBlank(),
                ],
                'enabled' => [
                    new Assert\NotBlank(),
                    new Assert\Choice(choices:  [0, 1, false, true,], message: $this->translator->trans('Scop.PlatformRedirecter.validation.choice'))
                ],
                'queryParamsHandling' =>  [
                    new Assert\Choice(choices:  [0, 1, 2], message: $this->translator->trans('Scop.PlatformRedirecter.validation.choice'))
                ]
            ],
            'allowExtraFields' => true,
            'allowMissingFields' => true,
        ]);
    }
}