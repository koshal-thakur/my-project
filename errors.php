<?php
    if(count($errors) > 0):
?>
<div class="error-list">
    <?php foreach($errors as $error): ?>
    <p><?php echo htmlspecialchars($error) ?></p>
    <?php endforeach ?>
</div>
<?php endif ?>