<?php

namespace App\Http\Controllers;

use App\Domain\Services\UserServiceInterface;
use App\Http\Requests\Auth\RegisterRequest;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\Http\Controllers\AccessTokenController;
use Laravel\Passport\RefreshTokenRepository;
use Laravel\Passport\TokenRepository;
use League\OAuth2\Server\AuthorizationServer;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends AccessTokenController
{
    use ApiResponse;

    private UserServiceInterface $userService;

    public function __construct(
        AuthorizationServer  $authorizationServer,
        TokenRepository      $tokenRepository,
        UserServiceInterface $userService
    )
    {
        parent::__construct($authorizationServer, $tokenRepository);
        $this->userService = $userService;
    }

    /**
     * @OA\Post (
     ** path="/api/user/register",
     *   tags={"Авторизация"},
     *   summary="Вход",
     *   operationId="register",
     *  @OA\Parameter(
     *      name="name",
     *      in="query",
     *      required=true,
     *      @OA\Schema(
     *           type="string"
     *      )
     *   ),
     *  @OA\Parameter(
     *      name="email",
     *      in="query",
     *      required=true,
     *      @OA\Schema(
     *           type="string"
     *      )
     *   ),
     *   @OA\Parameter(
     *      name="password",
     *      in="query",
     *      required=true,
     *      @OA\Schema(
     *           type="string"
     *      )
     *   ),
     *   @OA\Parameter(
     *      name="password_confirmation",
     *      in="query",
     *      required=true,
     *      @OA\Schema(
     *           type="string"
     *      )
     *   ),
     *   @OA\Response(
     *      response=201,
     *       description="Success",
     *      @OA\JsonContent(
     *          type="object",
     *          @OA\Property(property="success", type="boolean", example="true"),
     *          @OA\Property(property="message", type="string", example="Пользователь успешно зарегистрирован"),
     *      )
     *   ),
     *   @OA\Response(
     *      response=422,
     *      description="Validaion Failed"
     *   )
     *)
     *
     * @param RegisterRequest $request
     * @return JsonResponse
     */

    public function register(RegisterRequest $request): JsonResponse
    {
        $user = $this->userService->create($request->validated());

        return $this->successResponse(
            $user,
            Response::HTTP_CREATED,
            'Пользователь успешно зарегистрирован'
        );
    }

    /**
     * @OA\Post (
     *     path="/api/user/login",
     *     summary = "Login",
     *     operationId="auth.login",
     *     tags={"Авторизация"},
     *     @OA\RequestBody(
     *       required=true,
     *       @OA\MediaType(
     *         mediaType="application/json",
     *         @OA\Schema(
     *           @OA\Property(
     *             property="email",
     *             description="Email",
     *             type="string",
     *             example="test@test.com",
     *           ),
     *          @OA\Property(
     *             property="password",
     *             description="Password",
     *             type="string",
     *             example="admin",
     *           ),
     *         ),
     *       ),
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Success Login",
     *     ),
     *     @OA\Response(
     *          response="404",
     *          description="User does not exist",
     *      ),
     *     @OA\Response(
     *         response="401",
     *         description="Unauthorized",
     *     )
     * )
     *
     * @param ServerRequestInterface $request
     * @return JsonResponse
     */

    public function login(ServerRequestInterface $request): JsonResponse
    {
        $inputParams = $request->getParsedBody();
        $inputEmail = $inputParams['email'];
        $inputPassword = $inputParams['password'];
        $user = $this->userService->get(['email' => $inputEmail]);

        if (!Hash::check($inputPassword, $user->password)) {
            return $this->errorResponse(
                null,
                Response::HTTP_UNAUTHORIZED,
                'Недействительные учетные данные'
            );
        }

        $request = $request->withParsedBody([
            'grant_type' => 'password',
            'client_id' => env('CLIENT_ID'),
            'client_secret' => env('CLIENT_SECRET'),
            'username' => $inputEmail,
            'password' => $inputPassword,
            'scope' => '*',
        ]);

        return $this->successResponse(
            json_decode($this->issueToken($request)->getContent(), true),
            Response::HTTP_OK,
            'Вы вошли'
        );

    }

    /**
     * @OA\Post (
     *     path="/api/user/logout",
     *     summary = "Logout",
     *     operationId="auth.logout",
     *     tags={"Авторизация"},
     *     security={ {"bearer": {} }},
     *     @OA\Response(
     *         response="200",
     *         description="Success Logout",
     *     )
     * )
     *
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        $refreshTokenRepository = app(RefreshTokenRepository::class);
        $token = $request->user('api')->token();

        $refreshTokenRepository->revokeRefreshTokensByAccessTokenId($token->id);
        $token->revoke();

        return $this->successResponse(null, Response::HTTP_OK, 'Успешный выход из системы');
    }

    /**
     * @OA\Post (
     *     path="/api/user/refresh-token",
     *     summary = "Refresh",
     *     operationId="auth.refresh",
     *     tags={"Авторизация"},
     *     @OA\RequestBody(
     *       required=false,
     *       @OA\MediaType(
     *         mediaType="application/json",
     *         @OA\Schema(
     *           @OA\Property(
     *             property="refresh_token",
     *             description="Refresh Token",
     *             type="string",
     *           ),
     *         ),
     *       ),
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Refresh Token",
     *     )
     * )
     *
     * @param ServerRequestInterface $request
     * @return JsonResponse
     */

    public function refresh(ServerRequestInterface $request): JsonResponse
    {
        $request = $request->withParsedBody([
            'grant_type' => 'refresh_token',
            'client_id' => env('CLIENT_ID'),
            'client_secret' => env('CLIENT_SECRET'),
            'refresh_token' => request()->refresh_token,
            'scope' => '*',
        ]);

        return $this->successResponse(
            json_decode($this->issueToken($request)->getContent(), true)
        );
    }
}
