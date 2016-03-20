<!DOCTYPE html>

<html>

<head>

    {assign var='tpl' value=$pathToIncTemplate|cat:'new/head.tpl'}
    {include file=$tpl}

</head>

<body>

<div class="b-wrapper">

    {assign var='tpl' value=$pathToIncTemplate|cat:'new/block/b-header_no_marketing.tpl'}
    {include file=$tpl}

    <!-- Main Container -->
    <div class="b-body">
        <div class="b-contents">
            <div class="b-columns">
                <div class="b-columns-column b-columns-column__wide f-ib">
                    <p>Ooops, this is 404 page!</p>
                    <p>Things, you can do:</p>
                    <p>Deal with it.</p>
                </div>
            </div>
        </div>
    </div>


    <!-- Google Search -->
    {assign var='tpl' value=$pathToIncTemplate|cat:'new/block/b-google.tpl'}
    {include file=$tpl}

    <!-- Footer -->
    {assign var='tpl' value=$pathToIncTemplate|cat:'new/block/b-footer.tpl'}
    {include file=$tpl}

</div>

<!-- Banner Parallax -->
{literal}
    <script src="/new/js/jquery.parallax.js"></script>
    <script>
        $('#scene').parallax({
            calibrateX: false,
            calibrateY: false,
            invertX: false,
            invertY: false,
            limitX: false,
            limitY: 10,
            scalarX: 5,
            scalarY: 0,
            frictionX: 0.5,
            frictionY: 0.2
        });
    </script>
{/literal}
<!-- ! Banner Parallax -->

</body>

</html>