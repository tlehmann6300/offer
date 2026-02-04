<?php
echo '<h1>Der Server antwortet!</h1>';
echo 'Ich liege im Verzeichnis: ' . __DIR__;
echo '<br>Domain Aufruf war: ' . $_SERVER['HTTP_HOST'];
?>