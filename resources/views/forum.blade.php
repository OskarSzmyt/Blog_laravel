<!DOCTYPE html>
<html>
<head>
    <title>Demo - Zadanie 7 - WWW i jezyki skryptowe</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=0">
    <meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate" />
    <meta http-equiv="Pragma" content="no-cache" />
    <link rel="stylesheet" type="text/css" href="{{ asset('style.css') }}">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
    <script src="{{ asset('zadanie7.js') }}"></script>
</head>
<body>
<header>
    <h1>Demo - Zadanie 7</h1>
    <h2>Forum - dynamiczne modyfikacje strony, JavaScript, jQuery i AJAX</h2>
</header>
<nav class="menu"><a href="{{ url('/') }}">Forum</a></nav>

@if($context === 'topics' || $context === 'posts' || $context === 'images')
<section class="user-info">
    @if($user && (int) $user->userlevel === 10)
    <div>
        <a href="?cmd=topics">Tematy</a>
        <a href="?cmd=images">Obrazki</a>
        @if($context === 'topics')
        <a href="?cmd=userlist">Lista uczestników</a>
        @endif
    </div>
    @endif
    Zalogowany jako: {{ $user?->username }} ({{ $user?->userid }}) <a href="?cmd=logout">WYLOGUJ</a>

    @if($showUserList)
    <br />
    <table>
        <tr><th>Identyfikator</th><th>Nazwa</th><th>Poziom</th><th></th></tr>
        @foreach($users as $v)
        <tr>
            <td>{{ $v->userid }}</td>
            <td>{{ $v->username }}</td>
            <td>{{ (int) $v->userlevel === 10 ? 'admin' : 'user' }}</td>
            <td>
                @if($v->userid !== 'admin')
                <a href="?cmd=changeuser&userid={{ $v->userid }}">Zmień</a>
                <a class="danger" href="?cmd=deluser&userid={{ $v->userid }}">Kasuj</a>
                @endif
            </td>
        </tr>
        @endforeach
    </table>
    @endif
</section>
@endif

@if($context === 'topics')
<section>
    <nav><a href="" id="addtopic">+ Dodaj temat</a></nav>
    @if($topics->isEmpty())
    <p>To forum nie zawiera jeszcze żadnych tematów!</p>
    @else
    @foreach($topics as $v)
    <article class="topic">
        <header> </header>
        <div><a href="?cmd=posts&id={{ $v->topicid }}">{{ e($v->topic) }}</a></div>
        <footer>
            @if($user && (int) $user->userlevel === 10)
            <nav>
                <a href="#" topicid="{{ $v->topicid }}" class="topicedit">EDYTUJ</a>
                <a class="danger" href="?id={{ $v->topicid }}&cmd=topicdelete">KASUJ</a>
            </nav>
            @endif
            ID: {{ $v->topicid }}, Autor: {{ e(optional($users->get($v->userid))->username) }},
            Utworzono: {{ $v->date }}, Liczba wpisów: {{ $postCounts[$v->topicid] ?? 0 }}
        </footer>
    </article>
    @endforeach
    @endif

    <div class="modal" id="modal_topic">
        <form action="{{ url('/') }}" method="post">
            @csrf
            <header><h2>Dodaj nowy temat do dyskusji</h2></header>
            <input type="text" name="topic" placeholder="Nowy temat" autofocus value="{{ $topic->topic ?? '' }}"><br />
            <textarea name="topic_body" cols="80" rows="10" placeholder="Opis nowego tematu">{{ $topic->topic_body ?? '' }}</textarea><br />
            <input type="hidden" name="topicid" value="{{ $topic->topicid ?? '' }}">
            <button type="submit">Zapisz</button>
        </form>
    </div>
</section>
@endif

@if($context === 'posts')
<section>
    <nav><table><tr><td style="width:33.3%;"></td><td style="width:33.3%;"><a href="?cmd=topics">Lista tematów</a></td><td style="width:33.3%;"></td></tr></table></nav>

    @if($topic)
    <article class="topic">
        <header>Temat dyskusji: <b>{{ e($topic->topic) }}</b></header>
        <div>{!! nl2br(e($topic->topic_body)) !!}</div>
        <footer>ID: {{ $topic->topicid }}, Autor: {{ e(optional($users->get($topic->userid))->username) }}, Data: {{ $topic->date }}</footer>
    </article>
    @endif

    <nav><a href="#" id="addpost">+ Dodaj wpis</a></nav>

    @if($posts->isEmpty())
    <p>To forum nie zawiera jeszcze żadnych głosów w dyskusji!</p>
    @else
    @foreach($posts as $v)
    <article class="post">
        <div>
            {!! nl2br(e($v->post)) !!}<br />
            @foreach($images as $img)
            @if((int) $img->postid === (int) $v->postid)
            <div class="image">
                <img src="?image={{ $img->id }}" /><br />
                {{ $img->title }}<br />
                @if($user && ((int) $user->userlevel === 10 || $user->userid === $v->userid))
                <a href="#" imgid="{{ $img->id }}" class="imgedit">EDYTUJ</a>
                <a class="danger" href="?cmd=imgdelete&imgid={{ $img->id }}">KASUJ</a>
                @endif
            </div>
            @endif
            @endforeach
        </div>
        <footer>
            <nav>
                @if($user && ((int) $user->userlevel === 10 || $user->userid === $v->userid))
                <a href="#" postid="{{ $v->postid }}" class="postedit">EDYTUJ</a>
                <a href="#" postid="{{ $v->postid }}" class="uploadfile">+ OBRAZEK</a>
                <a class="danger" href="?id={{ $v->postid }}&cmd=delete">KASUJ</a>
                @endif
            </nav>
            ID: {{ $v->postid }}, Autor: {{ e(optional($users->get($v->userid))->username) }}, Utworzono dnia: {{ $v->date }}
            <div style="clear:both;"></div>
        </footer>
    </article>
    @endforeach
    @endif

    <div class="modal" id="modal_post"><form action="{{ url('/') }}" method="post" enctype="multipart/form-data">@csrf
        <header><h2>Dodaj nową wypowiedź do dyskusji</h2></header>
        <textarea name="post" autofocus cols="80" rows="10" placeholder="Wpisz tu swoją wypowiedź."></textarea><br />
        <input type="hidden" name="postid" value="" />
        <button type="submit">Zapisz</button>
    </form></div>

    <div class="modal" id="modal_file"><form action="{{ url('/') }}" method="post" enctype="multipart/form-data">@csrf
        <header><h2>Dodaj ilustrację do wpisu ID: <span id="pid"></span></h2></header>
        <input type="file" name="image">
        <input type="text" name="imagetitle" value="" placeholder="Opis pliku" />
        <button type="submit">Prześlij</button>
        <input type="hidden" name="postid" value="" />
    </form></div>

    <div class="modal" id="modal_fileedit"><form action="{{ url('/') }}" method="post" enctype="multipart/form-data">@csrf
        <header><h2>Edytuj podpis</h2></header>
        <input type="text" name="imagetitle" value="" placeholder="Opis pliku" />
        <button type="submit">Zapisz</button>
        <input type="hidden" name="imgid" value="" />
    </form></div>

    @if($uploaderror !== '')<div class="error">{{ $uploaderror }}</div>@endif
</section>
@endif

@if($context === 'images')
<section class="images-table">
    <table>
        <caption>Lista plików graficznych</caption>
        <tr><th>Id</th><th>Obrazek</th><th>Uczestnik</th><th>Post</th><th>Nazwa</th><th>Data</th><th></th></tr>
        @foreach($images as $img)
        <tr>
            <td>{{ $img->id }}</td>
            <td class="image"><img src="?image={{ $img->id }}" /><br />{{ $img->title !== '' ? $img->title : '- brak -' }}</td>
            <td>{{ $img->userid }}<br />[{{ optional($users->get($img->userid))->username }}]</td>
            <td>{{ $img->postid }}</td>
            <td>{{ $img->name.$img->sufix }}</td>
            <td>{{ $img->date }}</td>
            <td><nav><a class="danger" href="?cmd=imgdelete&imgid={{ $img->id }}">KASUJ</a></nav></td>
        </tr>
        @endforeach
    </table>
</section>
@endif

@if($context === 'login')
<nav class="menu"><a href="#register">Rejestracja</a><a href="#login">Logowanie</a></nav>

<section class="modal" id="login"><form action="{{ url('/') }}" method="post">@csrf
    <header><h2>Zaloguj się do forum</h2></header>
    <input type="text" name="userid" placeholder="Nazwa logowania" pattern="[A-Za-z0-9\-]*" autofocus><br />
    <input type="password" name="pass" placeholder="Hasło"><br />
    <div class="error">{{ $error1 }}</div>
    <button type="submit">Zaloguj się</button>
</form></section>

<section class="modal" id="register"><form action="{{ url('/') }}" method="post">@csrf
    <header><h2>Jesli nie jesteś zarejestrowany, to możesz zapisać się do forum.</h2></header>
    <input type="text" name="userid" placeholder="Nazwa logowania (dozwolone są tylko: litery, cyfry i znak '-')" pattern="[A-Za-z0-9\-]*" autofocus><br />
    <input type="text" name="username" placeholder="Imię autora"><br />
    <input type="password" name="pass1" placeholder="Hasło"><br />
    <input type="password" name="pass2" placeholder="Powtórz hasło"><br />
    <div style="text-align:center;margin:10px 0;">
        <img src="?capthaimg=1" alt="captcha" title="Wpisz kod kontrolny z obrazka" onclick="this.src='?capthaimg='+Math.random();" /><br />
        'kliknij' na obrazek by zmienić kod kontrolny<br />
        <input style="width:300px;" type="text" name="captcha" placeholder="Wpisz kod kontrolny z obrazka">
    </div>
    <div class="error">{{ $error }}</div>
    <button type="submit">Zapisz się do forum</button>
</form></section>
@endif

<footer>Ostatni wpis na forum powstał dnia: {{ $lastPost }}</footer>
</body>
</html>
