<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class ForumController extends Controller
{
    private const CONTEXT_TOPICS = 'topics';
    private const CONTEXT_POSTS = 'posts';
    private const CONTEXT_IMAGES = 'images';

    public function index(Request $request): Response|JsonResponse|RedirectResponse
    {
        $this->ensureAdminExists();

        if ($request->query('capthaimg')) {
            return $this->captcha($request);
        }

        if ($request->filled('image')) {
            return $this->image((int) $request->query('image'));
        }

        if ($this->sessionTokenInvalid($request)) {
            return $this->logout($request);
        }

        if ($request->query('cmd') === 'logout') {
            return $this->logout($request);
        }

        $user = $this->currentUser($request);

        if ($user) {
            if ($request->query('cmd') === 'posts' && $request->filled('id')) {
                $request->session()->put('forum_context', self::CONTEXT_POSTS);
                $request->session()->put('forum_topicid', (int) $request->query('id'));

                return redirect('/');
            }

            if ($request->query('cmd') === 'topics') {
                $request->session()->put('forum_context', self::CONTEXT_TOPICS);
                $request->session()->forget('forum_topicid');

                return redirect('/');
            }

            if ($request->query('cmd') === 'images') {
                $request->session()->put('forum_context', self::CONTEXT_IMAGES);
                $request->session()->forget('forum_topicid');

                return redirect('/');
            }

            if ($request->query('cmd') === 'userlist' && $this->isAdmin($user)) {
                $request->session()->put('forum_userlist', !((bool) $request->session()->get('forum_userlist', false)));

                return redirect('/');
            }

            if ($request->query('cmd') === 'gettopic' && $request->filled('topicid')) {
                $topic = DB::table('topics')->where('topicid', (int) $request->query('topicid'))->first();

                return response()->json($topic ?: []);
            }

            if ($request->query('cmd') === 'getpost' && $request->filled('postid')) {
                $post = DB::table('posts')->where('postid', (int) $request->query('postid'))->first();

                return response()->json($post ?: []);
            }

            if ($request->query('cmd') === 'getimage' && $request->filled('imgid')) {
                $image = DB::table('images')->where('id', (int) $request->query('imgid'))->first();

                return response()->json($image ?: []);
            }

            if ($request->query('cmd') === 'imgdelete' && $request->filled('imgid')) {
                $this->deleteImage((int) $request->query('imgid'), $user);

                return redirect('/');
            }

            if ($request->query('cmd') === 'changeuser' && $request->filled('userid') && $this->isAdmin($user)) {
                $userid = (string) $request->query('userid');
                if ($userid !== 'admin') {
                    $target = DB::table('forum_users')->where('userid', $userid)->first();
                    if ($target) {
                        DB::table('forum_users')->where('userid', $userid)
                            ->update(['userlevel' => ((int) $target->userlevel === 10) ? 0 : 10]);
                    }
                }

                return redirect('/');
            }

            if ($request->query('cmd') === 'deluser' && $request->filled('userid') && $this->isAdmin($user)) {
                $userid = (string) $request->query('userid');
                if ($userid !== 'admin') {
                    $this->deleteUserWithContent($userid);
                }

                return redirect('/');
            }

            if ($request->query('cmd') === 'delete' && $request->filled('id')) {
                $post = DB::table('posts')->where('postid', (int) $request->query('id'))->first();
                if ($post && $this->canManageUserContent($user, (string) $post->userid)) {
                    $this->deleteImagesFromPost((int) $post->postid, $user);
                    DB::table('posts')->where('postid', (int) $post->postid)->delete();
                }

                return redirect('/');
            }

            if ($request->query('cmd') === 'topicdelete' && $request->filled('id') && $this->isAdmin($user)) {
                $topicId = (int) $request->query('id');
                $this->deleteTopicWithPosts($topicId, $user);

                return redirect('/');
            }
        }

        $context = $request->session()->get('forum_context');
        if (!$context || !$user) {
            $context = 'login';
        }

        $users = DB::table('forum_users')->orderBy('userid')->get()->keyBy('userid');

        $data = [
            'context' => $context,
            'user' => $user,
            'users' => $users,
            'showUserList' => (bool) $request->session()->get('forum_userlist', false),
            'lastPost' => DB::table('posts')->orderByDesc('postid')->value('date') ?? '- brak wpisów -',
            'error' => session('error', ''),
            'error1' => session('error1', ''),
            'uploaderror' => session('uploaderror', ''),
            'topic' => null,
            'topics' => collect(),
            'posts' => collect(),
            'images' => collect(),
            'postCounts' => collect(),
        ];

        if ($context === self::CONTEXT_TOPICS && $user) {
            if ($request->query('cmd') === 'topicedit' && $request->filled('id') && $this->isAdmin($user)) {
                $data['topic'] = DB::table('topics')->where('topicid', (int) $request->query('id'))->first();
            }

            $data['topics'] = DB::table('topics')->orderByDesc('date')->get();
            $data['postCounts'] = DB::table('posts')->selectRaw('topicid, COUNT(*) as cnt')->groupBy('topicid')->pluck('cnt', 'topicid');
        }

        if ($context === self::CONTEXT_POSTS && $user) {
            $topicId = (int) $request->session()->get('forum_topicid', 0);
            if ($topicId > 0) {
                $data['topic'] = DB::table('topics')->where('topicid', $topicId)->first();
                $data['posts'] = DB::table('posts')->where('topicid', $topicId)->orderByDesc('date')->get();
                $data['images'] = DB::table('images')->where('topicid', $topicId)->orderBy('id')->get();
            }
        }

        if ($context === self::CONTEXT_IMAGES && $user) {
            $data['images'] = DB::table('images')->orderByDesc('date')->get();
        }

        return response()->view('forum', $data);
    }

    public function submit(Request $request): RedirectResponse
    {
        $this->ensureAdminExists();

        if ($request->filled('userid') && $request->has('pass') && !$request->has('pass1')) {
            $record = DB::table('forum_users')->where('userid', (string) $request->input('userid'))->first();
            if (!$record || (string) $record->pass !== md5((string) $request->input('pass'))) {
                return redirect('/')->with('error1', 'Bad user name or password!');
            }

            $this->startUserSession($request, (string) $record->userid);

            return redirect('/');
        }

        if ($request->filled('userid') && $request->filled('pass1')) {
            if ((string) $request->input('pass1') !== (string) $request->input('pass2')) {
                return redirect('/')->with('error', 'Hasła nie są takie same.');
            }

            if (strtoupper((string) $request->input('captcha')) !== strtoupper((string) $request->session()->get('captcha_code'))) {
                return redirect('/')->with('error', 'Wpisano niewłaściwy kod kontrolny');
            }

            $exists = DB::table('forum_users')->where('userid', (string) $request->input('userid'))->exists();
            if ($exists) {
                return redirect('/')->with('error', 'Bad username');
            }

            DB::table('forum_users')->insert([
                'userid' => (string) $request->input('userid'),
                'username' => (string) $request->input('username'),
                'userlevel' => 0,
                'pass' => md5((string) $request->input('pass1')),
            ]);

            $this->startUserSession($request, (string) $request->input('userid'));

            return redirect('/');
        }

        $user = $this->currentUser($request);
        if (!$user) {
            return redirect('/');
        }

        $context = $request->session()->get('forum_context', self::CONTEXT_TOPICS);

        if ($context === self::CONTEXT_TOPICS && $request->filled('topic') && $request->filled('topic_body')) {
            $payload = [
                'topic' => (string) $request->input('topic'),
                'topic_body' => (string) $request->input('topic_body'),
                'date' => now()->toDateTimeString(),
                'userid' => (string) $user->userid,
            ];

            $topicId = (string) $request->input('topicid');
            if ($topicId !== '') {
                $existing = DB::table('topics')->where('topicid', (int) $topicId)->first();
                if ($existing && $this->isAdmin($user)) {
                    DB::table('topics')->where('topicid', (int) $topicId)->update($payload);
                }
            } else {
                DB::table('topics')->insert($payload);
            }

            return redirect('/');
        }

        if ($context === self::CONTEXT_POSTS) {
            if ($request->filled('post')) {
                $postPayload = [
                    'post' => (string) $request->input('post'),
                    'userid' => (string) $user->userid,
                    'topicid' => (int) $request->session()->get('forum_topicid'),
                    'date' => now()->toDateTimeString(),
                ];

                $postId = (string) $request->input('postid', '');
                if ($postId !== '') {
                    $existing = DB::table('posts')->where('postid', (int) $postId)->first();
                    if ($existing && $this->canManageUserContent($user, (string) $existing->userid)) {
                        DB::table('posts')->where('postid', (int) $postId)->update(['post' => (string) $request->input('post')]);
                    }
                } else {
                    DB::table('posts')->insert($postPayload);
                }

                return redirect('/');
            }

            if ($request->hasFile('image') && $request->file('image')->isValid()) {
                $file = $request->file('image');
                $mime = $file->getMimeType();
                $allowed = [
                    'image/jpg' => '.jpg',
                    'image/jpeg' => '.jpg',
                    'image/png' => '.png',
                    'image/gif' => '.gif',
                ];
                if (!isset($allowed[$mime])) {
                    return redirect('/')->with('uploaderror', 'Bad file type!');
                }

                $suffix = $allowed[$mime];
                $name = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $title = trim((string) $request->input('imagetitle')) !== '' ? (string) $request->input('imagetitle') : $name;

                $id = DB::table('images')->insertGetId([
                    'userid' => (string) $user->userid,
                    'postid' => (int) $request->input('postid'),
                    'topicid' => (int) $request->session()->get('forum_topicid'),
                    'name' => $name,
                    'sufix' => $suffix,
                    'title' => $title,
                    'date' => now()->toDateTimeString(),
                ]);

                $targetPath = public_path('files/'.$id.$suffix);
                if (!$file->move(dirname($targetPath), basename($targetPath))) {
                    DB::table('images')->where('id', $id)->delete();

                    return redirect('/')->with('uploaderror', 'Upload error!');
                }

                return redirect('/');
            }

            if ($request->filled('imgid') && $request->has('imagetitle')) {
                $img = DB::table('images')->where('id', (int) $request->input('imgid'))->first();
                if ($img && $this->canManageUserContent($user, (string) $img->userid)) {
                    $title = trim((string) $request->input('imagetitle'));
                    DB::table('images')->where('id', (int) $img->id)->update([
                        'title' => $title !== '' ? $title : (string) $img->name,
                    ]);
                }

                return redirect('/');
            }
        }

        return redirect('/');
    }

    public function captcha(Request $request): Response
    {
        $fonts = [
            public_path('fonts/stormfaze.ttf'),
            public_path('fonts/hemihead.ttf'),
            public_path('fonts/leadcoat.ttf'),
            public_path('fonts/stocky.ttf'),
            public_path('fonts/arial.ttf'),
        ];

        $code = chr(random_int(ord('A'), ord('Z')))
            .chr(random_int(ord('A'), ord('Z')))
            .chr(random_int(ord('A'), ord('Z')))
            .chr(random_int(ord('A'), ord('Z')));

        $request->session()->put('captcha_code', $code);

        $im = imagecreatetruecolor(200, 50);
        $background = imagecolorallocate($im, 0, 0, 0);
        imagecolortransparent($im, $background);

        for ($n = 0; $n < strlen($code); $n++) {
            $color = imagecolorallocate($im, 150, 150, 150);
            imagettftext(
                $im,
                random_int(20, 28),
                random_int(-50, 50),
                10 + ($n * (200 / 4)),
                30,
                $color,
                $fonts[random_int(0, 4)],
                $code[$n]
            );
        }

        ob_start();
        imagepng($im);
        $content = (string) ob_get_clean();
        imagedestroy($im);

        return response($content, 200)->header('Content-Type', 'image/png');
    }

    public function image(int $id): Response
    {
        $img = DB::table('images')->where('id', $id)->first();
        if (!$img) {
            abort(404);
        }

        $path = public_path('files/'.$img->id.$img->sufix);
        if (!file_exists($path)) {
            abort(404);
        }

        $resource = match ((string) $img->sufix) {
            '.jpg', '.jpeg' => imagecreatefromjpeg($path),
            '.png' => imagecreatefrompng($path),
            '.gif' => imagecreatefromgif($path),
            default => null,
        };

        if (!$resource) {
            abort(500, 'Cannot render image');
        }

        $resource = imagescale($resource, 300);
        $font = public_path('fonts/stormfaze.ttf');
        $text = now()->toDateTimeString();
        $textAngle = random_int(-25, 25);
        $fontSize = 18;
        $color = imagecolorallocate($resource, 0, 255, 0);
        $box = imagettfbbox($fontSize, $textAngle, $font, $text);

        imagettftext(
            $resource,
            $fontSize,
            $textAngle,
            (int) ((imagesx($resource) - ($box[4] - $box[0])) / 2),
            (int) ((imagesy($resource) - ($box[3] - $box[6]) + $fontSize) / 2),
            $color,
            $font,
            $text
        );

        ob_start();
        imagepng($resource);
        $content = (string) ob_get_clean();
        imagedestroy($resource);

        return response($content, 200)->header('Content-Type', 'image/png');
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    private function ensureAdminExists(): void
    {
        $exists = DB::table('forum_users')->where('userid', 'admin')->exists();
        if (!$exists) {
            DB::table('forum_users')->insert([
                'userid' => 'admin',
                'username' => 'admin',
                'userlevel' => 10,
                'pass' => md5('admin'),
            ]);
        }
    }

    private function currentUser(Request $request): ?object
    {
        $userid = $request->session()->get('forum_userid');
        if (!$userid) {
            return null;
        }

        return DB::table('forum_users')->where('userid', (string) $userid)->first();
    }

    private function startUserSession(Request $request, string $userid): void
    {
        $request->session()->invalidate();
        $request->session()->regenerate();
        $request->session()->regenerateToken();

        $request->session()->put('forum_userid', $userid);
        $request->session()->put('forum_context', self::CONTEXT_TOPICS);
        $request->session()->put('forum_token', md5($request->session()->getId().self::class));
        $request->session()->forget('forum_topicid');
        $request->session()->forget('forum_userlist');
    }

    private function sessionTokenInvalid(Request $request): bool
    {
        $token = $request->session()->get('forum_token');
        if (!$token) {
            return false;
        }

        return $token !== md5($request->session()->getId().self::class);
    }

    private function isAdmin(object $user): bool
    {
        return (int) $user->userlevel === 10;
    }

    private function canManageUserContent(object $user, string $ownerUserId): bool
    {
        return $this->isAdmin($user) || (string) $user->userid === $ownerUserId;
    }

    private function deleteUserWithContent(string $userid): void
    {
        $postIds = DB::table('posts')->where('userid', $userid)->pluck('postid')->all();
        foreach ($postIds as $postId) {
            $this->deleteImagesFromPost((int) $postId, null);
        }

        $topicIds = DB::table('topics')->where('userid', $userid)->pluck('topicid')->all();
        foreach ($topicIds as $topicId) {
            $this->deleteTopicWithPosts((int) $topicId, null);
        }

        $images = DB::table('images')->where('userid', $userid)->pluck('id')->all();
        foreach ($images as $id) {
            $this->deleteImage((int) $id, null);
        }

        DB::table('forum_users')->where('userid', $userid)->delete();
    }

    private function deleteTopicWithPosts(int $topicId, ?object $actingUser): void
    {
        $posts = DB::table('posts')->where('topicid', $topicId)->pluck('postid')->all();
        foreach ($posts as $postId) {
            $this->deleteImagesFromPost((int) $postId, $actingUser);
            DB::table('posts')->where('postid', (int) $postId)->delete();
        }

        DB::table('topics')->where('topicid', $topicId)->delete();
    }

    private function deleteImagesFromPost(int $postId, ?object $actingUser): void
    {
        $ids = DB::table('images')->where('postid', $postId)->pluck('id')->all();
        foreach ($ids as $id) {
            $this->deleteImage((int) $id, $actingUser);
        }
    }

    private function deleteImage(int $id, ?object $actingUser): void
    {
        $img = DB::table('images')->where('id', $id)->first();
        if (!$img) {
            return;
        }

        if ($actingUser && !$this->canManageUserContent($actingUser, (string) $img->userid)) {
            return;
        }

        $path = public_path('files/'.$img->id.$img->sufix);
        if (file_exists($path)) {
            @unlink($path);
        }

        DB::table('images')->where('id', $id)->delete();
    }
}
