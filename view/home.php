<!DOCTYPE html>
<html>
    <head>
        <title>Demo view</title>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.1/jquery.min.js"></script>
    </head>
    <body>
        <?php if ($site) { ?>
            <table border =1>
                <?php foreach ($site as $input) { ?>
                    <tr>
                        <td><?php if (isset($isLocal[$input])) { ?>
                                <a href="/<?php echo $input; ?>" target ="_blank"><?php echo $input; ?></a>
                            <?php } else { ?>
                                <?php echo $input; ?>
                            <?php } ?>
                        </td>
                        <?php if (isset($running) && in_array($input, $running)) { ?>
                            <td colspan="2">...updating</td>
                        <?php } else { ?>
                            <td><?php if (isset($isLocal[$input])) { ?>
                                    <a href="?f=u&n=<?php echo $input; ?>">update</a>
                                <?php } else { ?>
                                    <a href="?f=c&n=<?php echo $input; ?>">demo</a>
                                <?php } ?>
                            </td>
                            <td><?php if (isset($isLocal[$input])) { ?>
                                    <a href="?f=d&n=<?php echo $input; ?>">delete</a>
                                <?php } else { ?>
                                    &nbsp;
                                <?php } ?>
                            </td>
                        <?php } ?>
                    </tr>    
                <?php } ?>
            </table>
        <?php } ?>
        <script type="text/javascript">
            function call() {
                $.ajax({
                    type: 'GET',
                    url: 'api.php',
                    success: function (r) {
                        if (r.reload){
                            location.reload();
                        }
                    },
                    failure: function (bad) {
                        console.log(bad);
                    }
                });
            }


            $(document).ready(function () {
                call();
                setInterval(function () {
                    call();
                }, 10000);
            });
        </script>
    </body>
</html>