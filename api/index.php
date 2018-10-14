<?php
// Headers/Session
header('Access-Control-Allow-Origin: *');
header('Content-type: application/json; charset=utf-8');

// Requirements
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
require '../vendor/autoload.php';
use JadissaPHPLib\Fw;

// Default configs
$config['displayErrorDetails'] = true;
$config['addContentLengthHeader'] = false;

// Database configs
$settings = json_decode(Fw::json_encode(Fw::getSettings()), true);
if (!$settings)
{
    die(json_last_error_msg());
}

$config['db']['host'] = $settings['database']['mysql']['host'];
$config['db']['user']   = $settings['database']['mysql']['username'];
$config['db']['pass']   = $settings['database']['mysql']['password'];
$config['db']['dbname'] = $settings['database']['mysql']['database'];
$config['debug'] = true;

// Slim initialization
$app = new \Slim\App([
    'settings' => $config
]);

// PDO/DB abstraction layer
$container = $app->getContainer();
$container['db'] = function($c)
{
    $db = $c['settings']['db'];
    $pdo = new PDO("mysql:host=" . $db['host'] . ";dbname=" . $db['dbname'], $db['user'], $db['pass']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    return $pdo;
};

// Session initialization
$app->add(new \Slim\Middleware\Session([
    'name' => 'dummy_session',
    'autorefresh' => true,
    'lifetime' => '1 hour'
]));
$container['session'] = function ($c) {
    return new \SlimSession\Helper;
};

// Logging
$container['logger'] = function($c)
{
    $logger = new \Monolog\Logger('my_logger');
    $file_handler = new \Monolog\Handler\StreamHandler('../logs/app.log');
    $logger->pushHandler($file_handler);
    return $logger;
};


$app->group('/api',function() use($app){

    // Dummy test
    $app->get('/hello/{name}', function(Request $request, Response $response)
    {
        $name = $request->getAttribute('name');
        $response->getBody()->write("Hello, $name");
        $this->logger->addInfo("We said hello to $name");

        return $response;
    });

    // Authenticate user
    $app->post('/login', function(Request $request, Response $response) use($container)
    {
        try
        {
            $data = $request->getParams();
            $service = new NotORM($container['db']);

            $result = $service->users()->where([
                'user_name' => $data['username'],
                'password_hash' => md5($data['password'])
            ])->fetch();

            if (!$result)
            {
                print Fw::json_encode([
                    'stat' => 'error',
                    'message' => 'Try again'
                ]);
            }
            else
            {
                $session = $this->session;
                $session->set('username', $data['username']);
                $session->set('authenticated', true);

                print Fw::json_encode([
                    'stat' => 'ok',
                    'message' => 'Successfully authenticated'
                ]);
            }
        }
        catch(Exception $e)
        {
            print Fw::json_encode([
                'stat' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    });

});

$app->run();