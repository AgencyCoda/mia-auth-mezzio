<?php

namespace Mia\Auth\Handler;

use Mia\Auth\Helper\JwtHelper;
use Mia\Auth\Model\MIAUser;

/**
 * Description of MiaRecoveryHanlder
 * 
 * @OA\Post(
 *     path="/mia-auth/recovery",
 *     summary="Recovery Password",
 *     tags={"Authentication"},
 *     @OA\RequestBody(
 *         description="Info of User",
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="application/json",                 
 *             @OA\Schema(
 *                  @OA\Property(
 *                      property="email",
 *                      type="string",
 *                      description="Email of user",
 *                      example="matias@agencycoda.com"
 *                  )
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *          response=200,
 *          description="successful operation",
 *          @OA\JsonContent(ref="#/components/schemas/MIAUser")
 *     )
 * )
 *
 * @author matiascamiletti
 */
class MiaRecoveryHandler extends \Mia\Core\Request\MiaRequestHandler
{
    use JwtHelper;

    public function __construct($config)
    {
        // Setear configuración inicial
        $this->setConfig($config);
    }

    public function handle(\Psr\Http\Message\ServerRequestInterface $request): \Psr\Http\Message\ResponseInterface
    {
        // Obtener parametros obligatorios
        $email = $this->getParam($request, 'email', '');
        // Verificar si ya existe la cuenta
        $account = \Mia\Auth\Model\MIAUser::where('email', $email)->first();
        if($account === null){
            return new \Mia\Core\Diactoros\MiaJsonResponse(true);
            //return new \Mia\Core\Diactoros\MiaJsonErrorResponse(-1, 'Este email no existe');
        }
        if($account->deleted == 1){
            return new \Mia\Core\Diactoros\MiaJsonResponse(true);
            //return new \Mia\Core\Diactoros\MiaJsonErrorResponse(-1, 'This account not exist.');
        }
        // Valid if user is active
        if($this->validStatus && $account->status == MIAUser::STATUS_PENDING){
            return new \Mia\Core\Diactoros\MiaJsonResponse(true);
            //return MiaErrorHelper::toLangEs($request, -4, 'Tu cuenta no está activa', 'Your account is not active.');
        }else if($this->validStatus && $account->status == MIAUser::STATUS_BLOCKED){
            return new \Mia\Core\Diactoros\MiaJsonResponse(true);
            //return MiaErrorHelper::toLangEs($request, -5, 'Tu cuenta esta bloqueada', 'Your account is blocked.');
        }
        // Generar registro de token
        $token = \Mia\Auth\Model\MIAUser::encryptPassword($email . '_' . time() . '_' . $email);
        $recovery = new \Mia\Auth\Model\MIARecovery();
        $recovery->user_id = $account->id;
        $recovery->status = \Mia\Auth\Model\MIARecovery::STATUS_PENDING;
        $recovery->token = $token;
        $recovery->save();
        
        $lang = $this->getParam($request, 'lang', 'en');
        /* @var $sendgrid \Mia\Mail\Service\Sendgrid */
        $sendgrid = $request->getAttribute('Sendgrid');
        $result = $sendgrid->send($account->email, 'recovery-password-' . $lang, [
            'firstname' => $account->firstname,
            'email' => $account->email,
            'email_encoded' => urlencode($account->email),
            'token' => $token
        ]);

        /*if($result === false){
            return new \Mia\Core\Diactoros\MiaJsonResponse(true);
            return new \Mia\Core\Diactoros\MiaJsonErrorResponse(-15, 'No se ha podido enviar el email');
        }*/

        // Devolvemos datos del usuario
        return new \Mia\Core\Diactoros\MiaJsonResponse(true);
    }
}
