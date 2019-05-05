<?php

declare(strict_types=1);

namespace Fesor\RequestObject;


use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationListInterface;

final class DefaultErrorResponseProvider implements ErrorResponseProvider
{
    /**
     * Returns error response in case of invalid request data.
     *
     * @param ConstraintViolationListInterface $errors
     *
     * @return Response
     */
    public function getErrorResponse(ConstraintViolationListInterface $errors)
    {
        return new JsonResponse([
            'message' => 'Please check your data',
            'errors' => array_map(function (ConstraintViolation $violation) {
                return [
                    'path' => $violation->getPropertyPath(),
                    'message' => $violation->getMessage(),
                ];
            }, iterator_to_array($errors)),
        ], 400);    }
}
