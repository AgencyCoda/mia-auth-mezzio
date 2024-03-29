<?php

namespace Mia\Auth\Model;

/**
 * Description of MIAProvider
 *
 * @author matiascamiletti
 */
class MIAProvider extends \Illuminate\Database\Eloquent\Model
{
    const PROVIDER_GOOGLE = 1;
    const PROVIDER_APPLE = 2;
    const PROVIDER_FACEBOOK = 3;
    const PROVIDER_TWITTER = 4;
    const PROVIDER_WHATSAPP = 5;
    const PROVIDER_GOTO = 6;
    
    protected $table = 'mia_provider';
}
