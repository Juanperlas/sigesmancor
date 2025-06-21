</div> <!-- Fin del contenedor de la aplicación -->

<!-- Scripts JS -->
<script src="<?php echo $baseUrl; ?>assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo $baseUrl; ?>assets/vendor/chartjs/chart.min.js"></script>
<script src="<?php echo $baseUrl; ?>assets/js/script.js"></script>
<?php if (isset($js_adicional)): ?>
    <?php foreach ($js_adicional as $js): ?>
        <?php if (strpos($js, 'http') === 0 || strpos($js, '/') === 0): ?>
            <script src="<?php echo $js; ?>"></script>
        <?php else: ?>
            <script src="<?php echo $baseUrl . $js; ?>"></script>
        <?php endif; ?>
    <?php endforeach; ?>
<?php endif; ?> 

</body>

</html>