<?php

namespace App\Http\Controllers\Discourse;

use App\Http\Controllers\Controller;
use App\Services\DiscourseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TopicController extends Controller
{
    public function index(Request $request, DiscourseService $discourse)
    {
        $username = Auth::user()->profile->forum_name;
        $page = (int) $request->input('page', 0);

        return ['success' => true, 'data' => $discourse->topics($username, $page)];
    }

    public function store(Request $request, DiscourseService $discourse)
    {
        $response = $discourse->createPost([
            'title' => $request->title,
            'raw' => $request->raw,
        ], Auth::user()->profile->forum_name);

        if (isset($response['failed'])) {
            return $response;
        }

        return ['success' => true, 'data' => $response];
    }

    public function update(Request $request, DiscourseService $discourse, string $id)
    {
        $response = $discourse->updateTopic(
            $id,
            ['title' => $request->title],
            Auth::user()->profile->forum_name
        );

        if (isset($response['failed'])) {
            return $response;
        }

        return ['success' => true, 'data' => $response];
    }

    public function show(DiscourseService $discourse, $id)
    {
        $username = Auth::user()->profile->forum_name;

        return ['success' => true, 'data' => $discourse->topic($id, $username)];
    }
}
