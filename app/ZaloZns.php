<?php

namespace App;

use Botble\Setting\Models\Setting;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;


class ZaloZns
{
    public function sendZaloMessage($templateId, $recipient, $params){
        $client = new Client();
        $setting = \setting('zalo_access_token','');
        $autoAccessToken = $this->getAccessToken();
        $phone = substr_replace($recipient,'84',0,1);
        $response = $client->post('https://business.openapi.zalo.me/message/template', [
            RequestOptions::HEADERS => [
                'Content-Type' => 'application/json',
                'access_token' => $autoAccessToken, // Add your access token here
            ],
            RequestOptions::JSON => [
                'phone' => $phone,
                'template_id' => $templateId,
                'template_data' => $params,
                'tracking_id' => 'tracking_id',
            ],
        ]);
    }


public function getAccessToken()
{
    $refresh_token = setting('zalo_refresh_token','');
    $secretKey = setting('zalo_secret_key','');
    $code_verifier = bin2hex(random_bytes(32));
    $code_challenge = $this->base64_url_encode_zalo(hash('sha256', $code_verifier, true));
    $client = new Client([
        'headers' => [
            'Content-Type' => 'application/x-www-form-urlencoded',
            'secret_key' => "${secretKey}",
        ]
    ]);
    $response = $client->post('https://oauth.zaloapp.com/v4/oa/access_token', [
        'form_params' => [
            'app_id' => setting('zalo_oa_id',''),
            'grant_type' => 'refresh_token',
            'refresh_token'=>$refresh_token,
        ]
    ]);

    $data = json_decode($response->getBody(), true);

//    Setting::updateOrCreate(['key'=>'zalo_refresh_token'],['value'=>$data['refresh_token']]);

    $data_set = [
        'zalo_refresh_token'=>$data['refresh_token'],
        'zalo_access_token'=>$data['access_token']
    ];

    foreach ($data_set as $settingKey => $settingValue){
        if (is_array($settingValue)) {
            $settingValue = json_encode(array_filter($settingValue));
        }
        setting()->set($settingKey, (string)$settingValue);
    }
    setting()->save();

    return $data['access_token'];

}

function base64_url_encode_zalo($input)
{
    return str_replace('=', '', strtr(base64_encode($input), '+/', '-_'));
}


}