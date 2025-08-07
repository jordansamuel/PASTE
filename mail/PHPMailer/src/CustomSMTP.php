<?php
namespace PHPMailer\PHPMailer;

class CustomSMTP extends SMTP
{
    public $oauthUserEmail;
    public $oauthAccessToken;

    public function authenticate(
        $username,
        $password,
        $authtype = null,
        $OAuth = null
    ) {
        if ($authtype === 'XOAUTH2' && !empty($this->oauthUserEmail) && !empty($this->oauthAccessToken)) {
            $this->sendCommand('AUTH XOAUTH2 ' . base64_encode("user=$this->oauthUserEmail\1auth=Bearer $this->oauthAccessToken\1\1"));
            $response = $this->getResponse();
            if (strpos($response, '235') !== false) {
                return true;
            }
            $this->setError("XOAUTH2 authentication failed: $response");
            error_log("CustomSMTP XOAUTH2 auth failed: $response");
            return false;
        }
        return parent::authenticate($username, $password, $authtype, $OAuth);
    }
}
?>