<?php

/* $Id: index.php 5289 2011-09-05 09:08:16Z andrey $ */

if (!defined('Auth_OpenID_VERIFY_HOST')) {
    define('Auth_OpenID_VERIFY_HOST', false);
}

ob_start();

do {

// Данная константа проверяется в index.php для предотвращения вывода
// сообщения "Нет прав для осуществления операции" из require/index.php
// в случае, если у запрошенного шаблона установлены права "только для
// зарегистрированных" и т.п.
    define("NC_AUTH_IN_PROGRESS", 1);
    $NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)).( strstr(__FILE__, "/") ? "/" : "\\" );

    include_once ($NETCAT_FOLDER."vars.inc.php");
    require_once ($ROOT_FOLDER . "connect_io.php");

    $url = $parsed_url['path'] . ($parsed_url['query'] ? '?' . $parsed_url['query'] : '');

    $url = trim(str_replace($nc_core->SUB_FOLDER, '', $_SERVER['PHP_SELF']), '/');
    $current_catalogue = $nc_core->catalogue->get_by_host_name($_SERVER['HTTP_HOST']);
    $SQL = "
        SELECT sub.Catalogue_ID,
            sub.Subdivision_ID
            FROM Subdivision AS sub
                WHERE sub.Catalogue_ID = " . +$current_catalogue['Catalogue_ID'] . "
                AND sub.ExternalURL LIKE '%" . $db->escape($url) . "%'";

    list($catalogue, $sub) = $db->get_row($SQL, ARRAY_N);

    require ($INCLUDE_FOLDER."index.php");

    if ($logoff) {
        Unauthorize();

        if ($REQUESTED_FROM) {
            if ($REQUESTED_BY == 'POST')
                $redirect = "http://".$HTTP_HOST.$nc_core->SUB_FOLDER."/";
            else
                $redirect = "http://".$HTTP_HOST.$REQUESTED_FROM;
        }

        if ($REDIRECT_STATUS == 'on') {
            if (!$redirect) $redirect = '/';
            header("Location:".$redirect);
            exit;
        } else {
            printf(NETCAT_MODULE_AUTH_MSG_SESSION_CLOSED, $REQUESTED_FROM);
        }
        break;
    }

    $nc_auth = nc_auth::get_object();

// Авторизация вконтакте
    if ($nc_vk && $nc_auth_vk->enabled() && ($ex_user_id = $nc_auth_vk->is_member())) {
        $userinfo = $nc_auth_vk->get_info();
        $ex_user_id = intval($ex_user_id);
        if ($userinfo && $ex_user_id) {
            $user_id = $db->get_var("SELECT User_ID FROM `Auth_ExternalAuth` WHERE Service = 'vk' AND ExternalUser = '".$ex_user_id."' ");
            if (!$user_id) {
                $user_id = $nc_auth_vk->make_user($userinfo, $ex_user_id);
            }
            $nc_core->user->authorize_by_id($user_id, NC_AUTHTYPE_EX);
        }
        header("Location: http://".$HTTP_HOST.$REQUESTED_FROM);
        exit;
    }

// Авторизация через facebook
    if ($nc_fb && $nc_auth_fb->enabled() && ($fb_token = $_GET['token'])) {
        if (function_exists('curl_init')) {
            $tuCurl = curl_init();
            curl_setopt($tuCurl, CURLOPT_URL, "https://graph.facebook.com/me?access_token=".$fb_token);
            curl_setopt($tuCurl, CURLOPT_PORT, 443);
            curl_setopt($tuCurl, CURLOPT_VERBOSE, 0);
            curl_setopt($tuCurl, CURLOPT_HEADER, 0);
            curl_setopt($tuCurl, CURLOPT_SSLVERSION, 3);
            curl_setopt($tuCurl, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($tuCurl, CURLOPT_RETURNTRANSFER, 1);

            $res = json_decode(curl_exec($tuCurl));
        } else {
            $res = null;
        }

        if ($res) {
            $userinfo = array();
            foreach ($res as $k => $v) {

                $userinfo[$k] = $v;
            }

            $fb_user_id = $db->escape($userinfo['id']);
            $pic_headers = get_headers("https://graph.facebook.com/$fb_user_id/picture?type=large", 1);
            $userinfo['picture'] = (isset($pic_headers['Location']) ? $pic_headers['Location'] : "http://graph.facebook.com/$fb_user_id/picture?type=large");

            if ($userinfo) {
                $user_id = $db->get_var("SELECT User_ID
                                         FROM `Auth_ExternalAuth`
                                             WHERE Service = 'fb'
                                               AND ExternalUser = ".$fb_user_id);
                if (!$user_id) {
                    $user_id = $nc_auth_fb->make_user($userinfo, $fb_user_id);
                }
                $nc_core->user->authorize_by_id($user_id, NC_AUTHTYPE_EX);
            }
            header("Location: http://".$HTTP_HOST.$REQUESTED_FROM);
            exit;
        }
    }


    if ($nc_twitter && $nc_auth_twitter->enabled() && !$_GET['oauth_token']) {
        $nc_auth_twitter->do_includes();
        $connection = $nc_auth_twitter->get_connection();
        $request_token = $connection->getRequestToken("http://".$HTTP_HOST.$HTTP_ROOT_PATH."modules/auth/?nc_twitter=1&REQUESTED_FROM=" . $REQUESTED_FROM);
        $_SESSION['oauth_token'] = $token = $request_token['oauth_token'];
        $_SESSION['oauth_token_secret'] = $request_token['oauth_token_secret'];
        switch ($connection->http_code) {
            case 200:
                /* Build authorize URL and redirect user to Twitter. */
                header('Location: '.$connection->getAuthorizeURL($token));
                break;
            default:
                /* Show notification if something went wrong. */
                echo 'Could not connect to Twitter. Refresh the page or try again later.';
        }
    }

// ответ от twiiter
    if ($nc_twitter && $nc_auth_twitter->enabled() && $_GET['oauth_token']) {
        $nc_auth_twitter->do_includes();
        $connection = $nc_auth_twitter->get_connection();
        $_SESSION['access_token'] = $connection->getAccessToken($_REQUEST['oauth_verifier']);
        unset($_SESSION['oauth_token']);
        unset($_SESSION['oauth_token_secret']);
        $userinfo = $connection->get('account/verify_credentials');
        if ($userinfo) {
            $twitter_user_id = $userinfo->id;
            $user_id = $db->get_var("SELECT User_ID FROM `Auth_ExternalAuth` WHERE Service = 'twitter' AND ExternalUser = '".$db->escape($twitter_user_id)."' ");
            if (!$user_id) {
                $user_id = $nc_auth_twitter->make_user($userinfo, $twitter_user_id);
            }
            $nc_core->user->authorize_by_id($user_id, NC_AUTHTYPE_EX);
        }
        header("Location: http://".$HTTP_HOST.$REQUESTED_FROM);
        exit;
    }


// запрос на OpenID авторизацию
    if ($openid_url && $nc_auth_openid->enabled()) {
        $nc_auth_openid->do_includes();
        $consumer = $nc_auth_openid->get_consumer();

        // Begin the OpenID authentication process.
        $auth_request = $consumer->begin($openid_url);

        // No auth request means we can't begin OpenID.
        if (!$auth_request) {
            echo NETCAT_MODULE_AUTH_OPEN_ID_INVALID;
            exit;
        }

        // Required and Optional
        $sreg_request = Auth_OpenID_SRegRequest::build(array('nickname'), array('fullname', 'email'));

        if ($sreg_request)
            $auth_request->addExtension($sreg_request);

        if ($auth_request->shouldSendRedirect()) {
            $redirect_url = $auth_request->redirectURL($nc_auth_openid->get_trust_root(), $nc_auth_openid->get_return_to());
            // If the redirect URL can't be built, display an error message.
            if (Auth_OpenID::isFailure($redirect_url)) {
                echo sprintf(NETCAT_MODULE_AUTH_OPEN_ID_COULD_NOT_REDIRECT_TO_SERVER, $redirect_url->message);
                exit;
            } else {
                header("Location: ".$redirect_url);
                exit;
            }
        } else {
            // Generate form markup and render it.
            $form_id = 'openid_message';
            $form_html = $auth_request->htmlMarkup($nc_auth_openid->get_trust_root(), $nc_auth_openid->get_return_to(), false, array('id' => $form_id));
            // Display an error if the form markup couldn't be generated; otherwise, render the HTML.
            if (Auth_OpenID::isFailure($form_html)) {
                echo sprintf(NETCAT_MODULE_AUTH_OPEN_ID_COULD_NOT_REDIRECT_TO_SERVER, $form_html->message);
                exit;
            } else {
                print $form_html;
                exit;
            }
        }
    }
// Ответ от OpenID-провайдера
    $open_id = '';
    if ($_GET['openid_mode'] && $nc_auth_openid->enabled()) {
        $nc_auth_openid->do_includes();
        $consumer = $nc_auth_openid->get_consumer();

        // Завершаем процесс авторизации, используя ответ сервера.
        $return_to = $nc_auth_openid->get_return_to();
        $return_to.= "?".$QUERY_STRING;

        $response = $consumer->complete($return_to);

        // Проверка состояние ответа
        if ($response->status == Auth_OpenID_CANCEL) {
            // This means the authentication was cancelled.
            $msg = NETCAT_MODULE_AUTH_OPEN_ID_CHECK_CANCELED;
        } else if ($response->status == Auth_OpenID_FAILURE) {
            // OpenID авторизация не удалась; display the error message.
            $msg = sprintf(NETCAT_MODULE_AUTH_OPEN_ID_AUTH_FAILED, $response->message);
        } else if ($response->status == Auth_OpenID_SUCCESS) {
            $open_id = $db->escape($nc_auth_openid->normalize_openid_url($_GET['openid_identity']));
            $user_id = $db->get_var("SELECT User_ID FROM `Auth_ExternalAuth` WHERE Service = 'openid' AND ExternalUser = '".$open_id."' ");
            $userinfo = array();
            if (!$user_id) {
                foreach ($response->signed_args as $k => $v) {
                    if (nc_preg_match("/openid\.sreg\.([a-z]+)/i", $v, $match)) {
                        $userinfo[$match[1]] = $response->message->args->values[$k];
                    }
                }
                $user_id = $nc_auth_openid->make_user($userinfo, $open_id);
            }
            $nc_core->user->authorize_by_id($user_id, NC_AUTHTYPE_EX);
        }

        header("Location: http://".$HTTP_HOST.$REQUESTED_FROM);
        exit;
    }

// попытка авторизации
    if ($AuthPhase && !$_GET['openid_mode']) {
        // каптча не показывалась
        if ($nc_auth->need_captcha() && !array_key_exists('nc_captcha_code', $nc_core->input->fetch_get_post())) {
            $IsAuthorized = 0;
        }
        // каптча введена не правильно
        else if ($nc_auth->need_captcha() && !nc_captcha_verify_code($nc_core->input->fetch_get_post('nc_captcha_code'))) {
            $nc_auth->set_invalid_captcha();
            $IsAuthorized = 0;
        } else {
            $IsAuthorized = $nc_core->user->authorize_by_pass($AUTH_USER, $AUTH_PW);
        }
    }

    if ((!$AuthPhase || !$IsAuthorized) && !$nc_core->get_settings('user_login_form_disable', 'auth')) {
        $nc_auth->login_form();
    } else {
        $redirect = "http://".$HTTP_HOST.$REQUESTED_FROM;

        $ProjectDomain = GetAllProjectDomains();

        $Password = $db->get_var("SELECT ".$nc_core->MYSQL_ENCRYPT."('${AUTH_PW}')");

        if ($REDIRECT_STATUS == 'on') {
            if ($AUTHORIZATION_TYPE == 'session') {
                if (substr($redirect, -1) != 'l' && substr($redirect, -1) != '/') {
                    $redirect .= ( "&".session_name()."=".session_id());
                } else {
                    $redirect .= ( "?".session_name()."=".session_id());
                }
            }
            header('Location:'.$redirect);
            exit;
        } else {
            printf(NETCAT_MODULE_AUTH_MSG_AUTH_SUCCESS, $REQUESTED_FROM);
        }
    }

	// OAuth авторизация
	if ($_GET['nc_oauth'] && $nc_auth_oauth->enabled()){


		$providers = unserialize($nc_core->get_settings('ex_oauth_providers', 'auth'));
		$oauth_pr = array();
		foreach ($providers as $pr){
			$oauth_pr[$pr['provider']] = $pr;
		}


		// получен code
		if (isset($_GET['code'])) {

			$provider = $oauth_pr[$_GET['nc_oauth']];

			// получаем токен
			$tokenInfo = $nc_auth_oauth->get_access_token($provider['provider'],$_GET['code'],$provider['appid'],$provider['seckey']);

			if (isset($tokenInfo['access_token']) && isset($provider['pubkey'])) {

				$userInfo = $nc_auth_oauth->get_user_info($provider['provider'],$tokenInfo,$provider);

				$oauth_user_id = $userInfo['uid'];


				if ($userInfo) {
					$user_id = $db->get_var("SELECT User_ID
                                         FROM `Auth_ExternalAuth`
                                             WHERE Service = 'oauth'
                                               AND ExternalUser = ".$oauth_user_id);
					if (!$user_id) { // если юзер не зареган - регаем
						$user_id = $nc_auth_oauth->make_user($userInfo, $oauth_user_id);
					}
					$nc_core->user->authorize_by_id($user_id, NC_AUTHTYPE_EX);
				}
				header("Location: http://".$HTTP_HOST.$REQUESTED_FROM);
				exit;
			}
		};

	};

} while (false);

$nc_result_msg = ob_get_clean();

if ($File_Mode) {
    require_once $INCLUDE_FOLDER.'index_fs.inc.php';

    echo $template_header;
    echo $nc_result_msg;
    echo $template_footer;
} else {
    eval("echo \"".$template_header."\";");
    echo $nc_result_msg;
    eval("echo \"".$template_footer."\";");
}

?>