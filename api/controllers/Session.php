<?php
namespace API\Controllers;

use API\Models\User;
use Psr\Log\LoggerInterface;
use Psr\Http\Message\RequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\RequestException;

final class Session extends Base {

  private $facebook_config;

	function __construct(LoggerInterface $logger, \PDO $db, array $facebook_config) {
		parent::__construct($logger, $db, null);
    $this->facebook_config = $facebook_config;
  }

  // POST /api/session
  function create(Request $request, Response $response, array $args) {
    $body = $request->getParsedBody();
    // $facebook_code = $request->getParsedBodyParam('facebook_code');
    $client = new HttpClient();
    try {
      # Validamos el CODE enviado por el Login Dialog
      $data = $client->request('GET', 'https://graph.facebook.com/v2.5/oauth/access_token', [ 'query' => [
        'client_id'     => $this->facebook_config['client_id'],
        'client_secret' => $this->facebook_config['client_secret'],
        'redirect_uri'  => $this->facebook_config['redirect_uri'],
        'code'          => $body['facebook_code'],
      ]]);
      $token_info = json_decode($data->getBody()->getContents(), true);

      # Obtenemos los datos del usuario logueado (facebook_id, nombre y email)
      $data = $client->request('GET', 'https://graph.facebook.com/v2.5/me', [ 'query' => [
        'access_token' => $token_info['access_token'],
        'fields' => 'email,name,picture',
      ]]);
      $fb_user_info = json_decode($data->getBody()->getContents(), true);
      $this->logger->info("FB User > {$fb_user_info['email']} - {$fb_user_info['name']}");

    } catch (RequestException $e) {
      return $this->respond($response, ['error' => 'not_authorized'], 401);
    }

    # Buscamos el usuario de facebook en la base de datos
    $stmt = $this->db->prepare('SELECT * FROM users WHERE facebook_id = ?');
    $stmt->execute([ $fb_user_info['id'] ]);
    $user = $stmt->fetchObject(User::class);

    # Si no esta, verificamos si fue invitado por email
    if ($user == null) {
      $stmt = $this->db->prepare('SELECT * FROM users WHERE facebook_id is null AND email = ?');
      $stmt->execute([ $fb_user_info['email'] ]);
      $user = $stmt->fetchObject(User::class);

      $this->logger->debug("DB User ({$fb_user_info['email']}) > {$user->email} - {$user->name}");

      # Si no esta "invitado", retornamos un 403 Not Allowed
      if ($user == null) {
      $this->logger->warn("Not Allowed > {$fb_user_info['email']} - {$fb_user_info['name']}");
        return $this->respond($response, ['error' => 'not_allowed'], 403);

      } else {
        # Si esta, le actualizamos los datos de Facebook
        $this->logger->debug("Actualizando datos de FB de {$fb_user_info['email']}");
        $stmt = $this->db->prepare('UPDATE users SET facebook_id = :facebook_id, name = :name, avatar = :avatar WHERE email = :email');
        $stmt->execute([
          ':facebook_id' => $fb_user_info['id'],
          ':name'        => $fb_user_info['name'],
          ':avatar'      => $fb_user_info['picture']['data']['url'],
          ':email'       => $fb_user_info['email'],
        ]);
      }
    }

    # Generamos un access_token
    $access_token = substr(str_replace(['_', '-', '.'], '', $body['facebook_code']), 0, 99);
    $access_token_expiration = time() + (7 * 24 * 60 * 60);

    $stmt = $this->db->prepare('UPDATE users SET access_token = :access_token, access_token_expiration = :access_token_expiration WHERE email = :email');
    $stmt->execute([
      ':access_token'            => $access_token,
      ':access_token_expiration' => $access_token_expiration,
      ':email'                   => $fb_user_info['email'],
    ]);

		$request = $request->withAttribute('user', $user);

		return $this->returnJSON($response, [
			'access_token'   => $access_token,
			'token_type'     => 'bearer',
			'expires_in'     => 7 * 24 * 60 * 60,
			'account_name'   => $fb_user_info['name'],
			'account_avatar' => $fb_user_info['picture']['data']['url'],
		]);
  }

}
