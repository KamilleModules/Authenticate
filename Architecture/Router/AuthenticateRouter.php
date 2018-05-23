<?php


namespace Module\Authenticate\Architecture\Router;


use Authenticate\SessionUser\SessionUser;
use Bat\UriTool;
use Core\Services\Hooks;
use Kamille\Architecture\ApplicationParameters\ApplicationParameters;
use Kamille\Architecture\Request\Web\HttpRequestInterface;
use Kamille\Architecture\Response\Web\HttpResponseInterface;
use Kamille\Architecture\Response\Web\RedirectResponse;
use Kamille\Architecture\Router\Helper\RouterHelper;
use Kamille\Architecture\Router\Web\WebRouterInterface;
use Kamille\Ling\Z;
use Kamille\Services\XConfig;

class AuthenticateRouter implements WebRouterInterface
{

    public static function create()
    {
        return new static();
    }

    public function match(HttpRequestInterface $request)
    {

        if ("dual.back" !== $request->get("siteType")) {
            if (false === SessionUser::isConnected()) {
                if (true === $this->useSplashLoginForm($request)) {
                    return XConfig::get("Authenticate.controllerLoginForm");
                }
            } else {
                $dkey = XConfig::get("Authenticate.disconnectGetKey");
                if (array_key_exists($dkey, $_GET)) {
                    $get = $_GET;
                    unset($get[$dkey]);


                    $request->set("response", RedirectResponse::create(Z::uri(null, $get, true, true)));
                    SessionUser::disconnect();
                    Hooks::call("Authenticate_Router_onDisconnectAfter");

                    /**
                     * By not returning null, we make the router believe a controller was found,
                     * so that it doesn't loop the other routers.
                     */
                    return "";
                } else {

                    if (true === XConfig::get("Authenticate.allowSessionRefresh")) {
                        SessionUser::refresh();
                    }
                }
            }
        }
    }

    //--------------------------------------------
    //
    //--------------------------------------------
    private function useSplashLoginForm(HttpRequestInterface $request)
    {
        $useSplashForm = XConfig::get("Authenticate.useSplashLoginForm");
        if (is_bool($useSplashForm)) {
            return $useSplashForm;
        } else {
            if (is_string($useSplashForm) && $request->get("siteType") === $useSplashForm) {
                return true;
            }
        }
        return false;
    }
}