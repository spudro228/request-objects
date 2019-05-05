<?php

namespace Tests\Fesor\RequestObject;

use Fesor\RequestObject;
use Fesor\RequestObject\DefaultErrorResponseProvider;
use Fesor\RequestObject\Examples\Request\CustomizedPayloadRequest;
use Fesor\RequestObject\Examples\Request\RegisterUserRequest;
use Fesor\RequestObject\Examples\Request\ResponseProvidingRequest;
use Fesor\RequestObject\PayloadResolver;
use Fesor\RequestObject\RequestObjectBinder;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class RequestBinderTest extends TestCase
{
    /** @var  Request */
    private $request;

    /** @var  RequestObject\PayloadResolver */
    private $payloadResolver;

    /** @var  ValidatorInterface */
    private $validator;

    public function setUp()
    {
        $this->request = Request::create('/');

        $this->payloadResolver = $this->getMockForAbstractClass(\Fesor\RequestObject\PayloadResolver::class);
        $this->payloadResolver->method('resolvePayload')->willReturn([]);

        $this->validator = $this->getMockForAbstractClass(ValidatorInterface::class);
    }

    public function testRequestObjectBinding()
    {
        $this->validRequest();
        (new RequestObjectBinder($this->payloadResolver, $this->validator))
            ->bind($this->request, [$this, 'action']);

        self::assertTrue($this->request->attributes->has('requestObj'));
        self::assertInstanceOf(RequestObject\RequestObject::class, $this->request->attributes->get('requestObj'));
    }

    public function testRequestObjectBindingOnClosure()
    {
        $this->validRequest();
        (new RequestObjectBinder($this->payloadResolver, $this->validator))
            ->bind($this->request, function (RegisterUserRequest $requestObj) {
            });

        self::assertTrue($this->request->attributes->has('requestObj'));
        self::assertInstanceOf(RequestObject\RequestObject::class, $this->request->attributes->get('requestObj'));
    }

    public function testRequestObjectBindingOnInvokableObject()
    {
        $action = new class()
        {
            public function __invoke(RegisterUserRequest $requestObj)
            {
            }
        };

        (new RequestObjectBinder($this->payloadResolver, $this->validator))->bind($this->request, $action);
        self::assertTrue($this->request->attributes->has('requestObj'));
        self::assertInstanceOf(RequestObject\RequestObject::class, $this->request->attributes->get('requestObj'));
    }

    public function testPassErrorsToAction()
    {
        $this->validRequest();
        (new RequestObjectBinder($this->payloadResolver, $this->validator))
            ->bind($this->request, function (RegisterUserRequest $requestObj, ConstraintViolationList $errors) {
            });

        self::assertTrue($this->request->attributes->has('errors'));
        self::assertInstanceOf(ConstraintViolationList::class, $this->request->attributes->get('errors'));
    }

    public function testPassErrorsToActionOnInvalidRequest()
    {
        $this->invalidRequest();
        (new RequestObjectBinder($this->payloadResolver, $this->validator))
            ->bind($this->request, function (RegisterUserRequest $requestObj, ConstraintViolationList $errors) {
            });

        self::assertTrue($this->request->attributes->has('errors'));
        self::assertInstanceOf(ConstraintViolationList::class, $this->request->attributes->get('errors'));
    }

    public function testFailIfNoErrorResponseProviderFound()
    {
        $this->expectException(RequestObject\InvalidRequestPayloadException::class);
        $this->invalidRequest();
        (new RequestObjectBinder($this->payloadResolver, $this->validator))
            ->bind($this->request, function (RegisterUserRequest $requestObj) {
            });

        self::assertTrue($this->request->attributes->has('errors'));
        self::assertInstanceOf(ConstraintViolationList::class, $this->request->attributes->get('errors'));
    }

    public function testErrorResponseProvider()
    {
        $this->invalidRequest();
        $response = (new RequestObjectBinder($this->payloadResolver, $this->validator))
            ->bind($this->request, function (ResponseProvidingRequest $requestObj) {
            });

        self::assertInstanceOf(Response::class, $response);
    }

    public function testErrorResponseProviderAsDependency()
    {
        $errorProvider = $this->getMockForAbstractClass(RequestObject\ErrorResponseProvider::class);
        $errorProvider->expects(self::once())->method('getErrorResponse')->willReturn(new Response());

        $this->invalidRequest();
        (new RequestObjectBinder($this->payloadResolver, $this->validator, $errorProvider))
            ->bind($this->request, function (RegisterUserRequest $requestObj) {
            });
    }

    public function testRequestWithPayloadResolver()
    {
        $this->payloadResolver->expects(self::never())->method('resolvePayload');
        $this->validRequest();
        (new RequestObjectBinder($this->payloadResolver, $this->validator))
            ->bind($this->request, function (CustomizedPayloadRequest $requestObj) {
            });
    }

    public function testBindDefaultErrorResponseProvider()
    {
        $request = Request::create('/');

        $payloadResolver = $this->getMockForAbstractClass(PayloadResolver::class);
        $payloadResolver->method('resolvePayload')->willReturn([]);

        $validator = $this->getMockForAbstractClass(ValidatorInterface::class);
        $validator->method('validate')->willReturn(new ConstraintViolationList([
            new ConstraintViolation('test', 'test', [], [], 'test', null),
        ]));

        $requestObjectBinder = new RequestObjectBinder($payloadResolver, $validator, new DefaultErrorResponseProvider());
        $response = $requestObjectBinder->bind($request, function (RegisterUserRequest $registerUserRequest) {
        });

        self::assertInstanceOf(JsonResponse::class, $response);
    }

    private function validRequest()
    {
        $this->validator->method('validate')->willReturn(new ConstraintViolationList([]));
    }

    private function invalidRequest()
    {
        $this->validator->method('validate')->willReturn(new ConstraintViolationList([
            new ConstraintViolation('test', 'test', [], [], 'test', null),
        ]));
    }

    // fake
    public function action(RegisterUserRequest $requestObj)
    {
    }
}
