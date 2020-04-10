<?php
    namespace Dependencies\Router;

use Application\Application;
use Application\Container\Dependency;
use Closure;
    use Dependencies\Router\Route as Route;
    use Dependencies\Router\Router as Router;
    use Dependencies\Http\Request as Request;
    use Dependencies\Http\Respone as Respone;
    use Dependencies\Notification\EventClient as EventClient;
use Dependencies\Notification\Notification;
use Exception;

class DomainHandler extends EventClient {

        private $domains;

        private $alias;

        private $router;

        private $flag;

        private $stack;

        public $routes;

        public function __construct(Router $_router) {
            parent::__construct();
            $this->Init();

            $this->router = $_router;
            $this->domains = [];
            $this->stack = [];
            $this->flag = false;
            
            $this->SubscribeEvent($_router, 'onCreateRoute');
        }

        private function Init() {
            $this->AddEvent('onManageRoute');
        }

        public function Manage(string $_pattern, closure $_callback) {
            
            $this->Validate($_pattern);

            if (!isset($this->domains[$_pattern])) {
                $this->domains[$_pattern] = [];
            }
            
            $this->flag = true;

            $this->stack[] = $_pattern;
            
            $container = \Application\Container\DIContainer::GetInstance();

            $container->call($_callback);

            array_pop($this->stack);

            if (empty($this->stack)) $this->flag = false;
            
        }

        private function Validate(string $_pattern) {

            $domain_regex_pattern = '/^((\w|\{\w+\})([\w\-]*|\{\w+\})*(\w|\{\w+\})\.)?(\w|\{\w+\})+(\.([a-z]|\{[a-z]+\})+)?$/';

            // check if the domain pattern is valid
            if (!preg_match($domain_regex_pattern, $_pattern)) throw new Exception();

            // check duplication of domain pattern's parameters
            preg_match_all('/\{(.+?)\}/', $_pattern, $matches);

            $params = array_count_values($matches[1]);

            foreach ($params as $name => $time) {
                if ($time > 1) throw new \Exception("");
            }
        }

        // private function ResolveDomainPattern(string $_name) {

        //     return preg_replace('/\{.+?\}/', '{param}', $_name);
        // }

        protected function HandleEventNotification(\Dependencies\Event\EventArgs $_notification) {
            
            if ($_notification instanceof RouteCreateNotification) {
                
                $this->OnRouteCreated($_notification);
            }
            
        }
        
        private function OnRouteCreated(RouteCreateNotification $_notification) {
            
            $created_Route = $_notification->GetCreatedRoute();

            $report_domain = Route::DOMAIN_DEFAULT;

            if ($this->flag === true) {
                $current_manageDomain = end($this->stack);

                $report_domain = $current_manageDomain;
                //$pattern = $this->ResolveDomainPattern($current_SettingDomain);
                $this->domains[$current_manageDomain][] = $created_Route;
            }
            
            $noti = new  Notification($this, 'onManageRoute');

            $noti->SetState([
                'domain' => $report_domain,
                'route' => $created_Route,
                'action' => $_notification->GetState(),
            ]);

            $this->NotifyEvent('onManageRoute', $noti);
        }
    }