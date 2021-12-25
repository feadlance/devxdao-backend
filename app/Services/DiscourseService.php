<?php

namespace App\Services;

use App\User;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Support\Str;
use Psr\Http\Message\ResponseInterface;

class DiscourseService
{
    private array $config;

    private Client $client;

    public function __construct(array $config)
    {
        $this->config = $config;

        $this->client = new Client([
            'base_uri' => $this->config['url'],
            'headers' => [
                'Api-Key' => $this->config['api_key'],
                'Api-Username' => $this->config['admin_username'],
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
        ]);
    }

    public function createPost(array $data, string $username)
    {
        return $this->try(function () use ($data, $username) {
            return $this->json(
                $this->client->post('/posts.json', $this->by($username, [
                    'form_params' => $data,
                ]))
            );
        });
    }

    public function updatePost(int $id, array $data, string $username)
    {
        return $this->try(function () use ($id, $data, $username) {
            return $this->json(
                $this->client->put("/posts/{$id}.json", $this->by($username, [
                    'form_params' => $data,
                ]))
            );
        });
    }

    public function deletePost(string $id, string $username)
    {
        return $this->try(function () use ($id, $username) {
            $this->client->delete("/posts/{$id}.json", $this->by($username));

            return $this->post($id, $username);
        });
    }

    public function posts(string $username)
    {
        return $this->json($this->client->get('/posts.json', $this->by($username)));
    }

    public function postsByTopicId(int $id, string $username)
    {
        $result = $this->json($this->client->get("/t/{$id}/posts.json", $this->by($username)));

        return $result['post_stream']['posts'];
    }

    public function like(int $id, string $username)
    {
        return $this->try(function () use ($id, $username) {
            $response = $this->client->post('/post_actions.json', $this->by($username, [
                'form_params' => [
                    'id' => $id,
                    'post_action_type_id' => 2,
                    'flag_topic' => false,
                ],
            ]));

            return $this->json($response);
        });
    }

    public function unlike(int $id, string $username)
    {
        return $this->try(function () use ($id, $username) {
            $response = $this->client->delete("/post_actions/{$id}.json", $this->by($username, [
                'form_params' => [
                    'post_action_type_id' => 2,
                ],
            ]));

            return $this->json($response);
        });
    }

    public function post(int $id, string $username)
    {
        return $this->try(function () use ($id, $username) {
            $response = $this->client->get("/posts/{$id}.json", $this->by($username));

            return $this->json($response);
        });
    }

    public function isLikedTo(int $id, string $username)
    {
        $post = $post = $this->post($id, $username);

        $action = head(array_filter($post['actions_summary'] ?? [], fn ($action) => $action['id'] === 2));

        if ($action === false) {
            return false;
        }

        return $action['acted'] ?? false;
    }

    public function latest(string $username)
    {
        return $this->json($this->client->get('/latest.json', $this->by($username)));
    }

    public function search($term, string $username)
    {
        $response = $this->client->get('/search.json', $this->by($username, [
            'query' => [
                'q' => $term,
            ],
        ]));

        return $this->json($response);
    }

    public function register(User $user)
    {
        return $this->try(function () use ($user) {
            $response = $this->client->post('/users.json', [
                'form_params' => [
                    'name' => $user->name,
                    'username' => $user->profile->forum_name,
                    'email' => $user->email,
                    'password' => Str::random(32),
                    'active' => true,
                    'approved' => true,
                ],
            ]);

            return $this->json($response);
        });
    }

    public function user(string $username)
    {
        try {
            $response = $this->client->get("/u/{$username}.json");

            return $this->json($response);
        } catch (ClientException $e) {
            return null;
        }
    }

    private function json(ResponseInterface $response)
    {
        return json_decode($response->getBody()->getContents(), true);
    }

    private function by(string $username, array $data = [])
    {
        return array_merge($data, [
            'headers' => array_merge($data['headers'] ?? [], [
                'Api-Username' => $username,
            ])
        ]);
    }

    private function try(callable $callable)
    {
        try {
            return $callable();
        } catch (ClientException $e) {
            if (app()->environment('local')) {
                throw $e;
            } else {
                $errors = $this->json($e->getResponse())['errors'] ?? ['Please try again.'];

                return ['success' => false, 'message' => head($errors)];
            }
        }
    }
}
