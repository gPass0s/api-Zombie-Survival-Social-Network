<!doctype html>
<html lang="en-us">
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

		<!-- CSRF Token -->
		<meta name="csrf-token" content="{{ csrf_token() }}">

		<title>Baseball Players Info</title>

		<!-- Styles -->
		<link href="{{ asset('css/app.css') }}" rel="stylesheet">

		

		<!-- Fonts -->
    	<link rel="dns-prefetch" href="//fonts.gstatic.com">
		<link href="https://fonts.googleapis.com/css?family=Nunito" rel="stylesheet" type="text/css">

		<!-- Scripts -->
    	<script src="{{ asset('js/app.js') }}" defer></script>
    	<script defer src="https://use.fontawesome.com/releases/v5.0.13/js/all.js" integrity="sha384-xymdQtn1n3lH2wcu0qhcdaOpQwyoarkgLVxC/wZ5q7h9gHtxICrpcaSUfygqZGOe" crossorigin="anonymous"></script>
	</head>

	<body class="bg-light">

		<header>
			<nav> @yield('header') </nav>
		</header>

		<div class="container">
			
			@yield('content')

			<footer class="my-5 pt-5 text-muted text-center text-small">
				<p class="mb-1">&copy; By <a href=https://github.com/gPass0s> @pass0s</a></p>
			</footer>
			
		</div>

		
	</body>
</html>