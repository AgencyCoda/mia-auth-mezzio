<?php

namespace Mia\Auth\Handler\User;

use Mia\Auth\Model\MIAUser;
use Mia\Auth\Repository\MIAUserRepository;

/**
 * Description of BlockHandler
 * 
 * @OA\Get(
 *     path="/user/save",
 *     summary="User Save or Create",
 *     tags={"User"},
 *     @OA\Response(
 *          response=200,
 *          description="successful operation",
 *          @OA\JsonContent(ref="#/components/schemas/MIAUser")
 *     )
 * )
 *
 * @author matiascamiletti
 */
class SaveHandler extends \Mia\Auth\Request\MiaAuthRequestHandler
{
    /**
     * Valores extras a guarda
     * @var array
     */
    protected $extras = [];
    protected $allowNew = false;
    
    public function __construct($extras = [], $allowNew = false)
    {
        $this->extras = $extras;
        $this->allowNew = $allowNew;
    }

    public function handle(\Psr\Http\Message\ServerRequestInterface $request): \Psr\Http\Message\ResponseInterface
    {
        // Obtenemos ID si fue enviado
        $itemId = $this->getParam($request, 'id', '');
        // Buscar si existe el tour en la DB
        $item = MIAUser::find($itemId);
        // verificar si existe
        if($item === null && $this->allowNew == false){
            return new \Mia\Core\Diactoros\MiaJsonErrorResponse(1, 'The element is not exist.');
        } else if ($item === null && $this->allowNew) {
            $item = new MIAUser();
            $item->status = MIAUser::STATUS_ACTIVE;
        }
        // Guardamos data
        $item->firstname = $this->getParam($request, 'firstname', '');
        if($item->firstname == ''){
            $item->firstname = 'empty';
        }
        $item->lastname = $this->getParam($request, 'lastname', '');
        $item->phone = $this->getParam($request, 'phone', '');
        $item->photo = $this->getParam($request, 'photo', '');
        $item->role = $this->getParam($request, 'role', \Mia\Auth\Model\MIAUser::ROLE_GENERAL);

        // Procesar valores extras
        foreach($this->extras as $extra){
            $item->{$extra} = $this->getParam($request, $extra, '');
        }

        // Verificar si cambio el email
        $newEmail = $this->getParam($request, 'email', '');
        if($newEmail != $item->email){
            // Verificar si existe el nuevo email
            if(\Mia\Auth\Model\MIAUser::where('email', $newEmail)->first() !== null){
                return new \Mia\Core\Diactoros\MiaJsonErrorResponse(-3, 'The new email exist for other account.');
            }
            $item->email = $newEmail;
        }else{
            $item->email = $newEmail;
        }

        // Verify new password
        $newPassword = $this->getParam($request, 'password', '');
        if($newPassword != ''){
            $item->password = \Mia\Auth\Model\MIAUser::encryptPassword($newPassword);
        }
        
        try {
            $item->save();
        } catch (\Exception $exc) {
            return new \Mia\Core\Diactoros\MiaJsonErrorResponse(-3, $exc->getMessage());
        }

        // Devolvemos respuesta
        return new \Mia\Core\Diactoros\MiaJsonResponse($item->toArray());
    }
}