<!-- Charset -->
<meta charset="utf-8">

<!-- Meta Information -->
<title>{$meta_title}</title>



<meta name="description" content="{$structure.meta_description}">
<meta name="keywords" content="{$structure.meta_keywords}">

<meta name="author" content="Mjolnir" />
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" />
<!-- !Meta Information -->

<!-- Fonts -->
<link href='https://fonts.googleapis.com/css?family=Noto+Sans:400,700&subset=latin,cyrillic' rel='stylesheet' type='text/css'>

<!-- !Fonts -->

<!-- Stylesheets -->
<link  href="http://cdnjs.cloudflare.com/ajax/libs/fotorama/4.6.4/fotorama.css" rel="stylesheet"> <!-- 3 KB -->
<link rel="stylesheet" href="/css/style.css">
<!-- !Stylesheets -->

<!-- JavaScript -->
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>
<script type='text/javascript' src='/js/numeral.min.js'></script>
<script type='text/javascript' src='/js/js.cookie.js'></script>
<script type='text/javascript' src='/js/jquery.currencyConverter.js'></script>
<script type="text/javascript" src="/js/main.js"></script>
<script type='text/javascript' src='/js/knockout-3.4.0.js'></script>
<script type='text/javascript' src='/js/kobindings.js'></script>

<!-- !JavaScript -->

<!-- fotorama.css & fotorama.js. -->
<script src="http://cdnjs.cloudflare.com/ajax/libs/fotorama/4.6.4/fotorama.js"></script> <!-- 16 KB -->

{* SLY *}
<script src="/js/sly.min.js"></script>
<script>
	$(function () {
		$('.js-slyScroller').each(function () {
			var $this = $(this);
			$this.find('.js-slyScroller__frame')
				.sly({
					horizontal: 1,
					itemNav: 'basic',
					smart: 0,
					mouseDragging: 1,
					touchDragging: 1,
					releaseSwing: 1,
					startAt: 0,
					scrollBar: $this.find('.js-slyScroller__scrollBar'),
					scrollBy: 1,
					pagesBar: $('.pages'),
					activatePageOn: 'click',
					speed: 300,
					elasticBounds: 1,
					dragHandle: 1,
					dynamicHandle: 1,
					clickBar: 1,

					// Buttons
					prevPage: $this.find('.js-slyScrollerPrev'),
					nextPage: $this.find('.js-slyScrollerNext')
				});
		});
	});

	$(window).on('resize orientationchange', function () {
		$('.js-slyScroller').each(function () {
			console.log('!');
			$(this).find('.js-slyScroller__frame').sly('reload');
		});
	});
</script>