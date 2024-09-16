<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Response;
use Illuminate\Validation\UnauthorizedException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\CssSelector\Exception\InternalErrorException;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\Response as ResponseCode;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (ValidationException $e) {
            return Response::json([
                "status" => "REQUEST_VALIDATION_ERROR",
                "message" => "Validation Error",
                "data" => null,
                "errors" => $e->validator->errors()
            ])->setStatusCode(ResponseCode::HTTP_BAD_REQUEST);
        });

        $exceptions->render(function (NotFoundHttpException $e) {
            return Response::json([
                "status" => "REQUEST_NOT_FOUND",
                "message" => $e->getMessage() ?? "Not Found",
                "data" => null,
                "errors" => null
            ])->setStatusCode(ResponseCode::HTTP_NOT_FOUND);
        });

        $exceptions->render(function (UnauthorizedException $e) {
            return Response::json([
                "status" => "UNAUTHORIZED",
                "message" => $e->getMessage() ?? "Unauthorized",
                "data" => null,
                "errors" => null
            ])->setStatusCode(ResponseCode::HTTP_UNAUTHORIZED);
        });

        $exceptions->render(function (BadRequestException $e) {
            return Response::json([
                "status" => "BAD_REQUEST",
                "message" => $e->getMessage() ?? "Bad Request",
                "data" => null,
                "errors" => null
            ])->setStatusCode(ResponseCode::HTTP_BAD_REQUEST);
        });

        $exceptions->render(function (Exception $e) {
            return Response::json([
                "status" => "INTERNAL_SERVER_ERROR",
                "message" => $e->getMessage() ?? "Something went wrong",
                "data" => null,
                "errors" => null
            ])->setStatusCode(ResponseCode::HTTP_INTERNAL_SERVER_ERROR);
        });
    })->create();
