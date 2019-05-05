<?php

declare(strict_types=1);

namespace Fesor\RequestObject\Examples\Request;

use Symfony\Component\Validator\Constraints as Assert;
use Fesor\RequestObject\RequestObject;

class ResponseWithCustomErrorProvider extends RequestObject
{
    public function rules()
    {
        return new Assert\Collection([
            'test' => new Assert\NotBlank(),
        ]);
    }
}