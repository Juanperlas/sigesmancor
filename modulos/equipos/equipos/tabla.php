<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SITUACIÓN POR IPRESS</title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Exo+2&display=swap" rel="stylesheet">
</head>

<body>
    <div class="container">
        <div class="panel">
            <div class="panel-heading">
                <h2 class="panel-title">SITUACIÓN POR IPRESS</h2>
            </div>
            <div class="panel-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th class="text-center" rowspan="2">IPRESS</th>
                                <th class="text-center" rowspan="2">DESCRIPCIÓN</th>
                                <th class="text-center" rowspan="2">STOCK<br>ACTUAL</th>
                                <th class="text-center" rowspan="2">CONSUMO<br>MAX.</th>
                                <th class="text-center" rowspan="2">COBERT.</th>
                                <th class="text-center" colspan="7">AÑO 2024</th>
                                <th class="text-center" colspan="12">AÑO 2025</th>
                            </tr>
                            <tr>
                                <th class="text-center">SET</th>
                                <th class="text-center">OCT</th>
                                <th class="text-center">NOV</th>
                                <th class="text-center">DIC</th>
                                <th class="text-center">ENE</th>
                                <th class="text-center">FEB</th>
                                <th class="text-center">MAR</th>
                                <th class="text-center">ABR</th>
                                <th class="text-center">MAY</th>
                                <th class="text-center">JUN</th>
                                <th class="text-center">JUL</th>
                                <th class="text-center">AGO</th>
                                <th class="text-center">SEP</th>
                                <th class="text-center">OCT</th>
                                <th class="text-center">NOV</th>
                                <th class="text-center">DIC</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $facilities = [
                                ['2203', 'HOSPITAL III BASE PUNO', 7224, 4289, '1.68', [3601, 3331, 2962, 2501, 4289, 3456, -352, -3161]],
                                ['2204', 'C.A.P. III METROPOLITANO', 720, 384, '1.88', [452, 380, 353, 295, 95, 384, -660, 92]],
                                ['2205', 'POLICLINICO ILAVE', 312, 58, '5.38', [226, 258, 252, 132, -20, 58, 4, 32]],
                                ['2206', 'H. I CLINICA UNIVERSITARIA', 389, 120, '3.24', [402, 472, 430, 276, 24, -57, -76, 120]],
                                ['2207', 'POSTA MEDICA JULI', 295, 151, '1.95', [94, 173, 115, 79, -164, 151, -116, 13]],
                                ['2208', 'POSTA MEDICA YUNGUYO', 185, 38, '4.87', [192, 251, 144, 101, -45, -2, 38, 25]],
                                ['2209', 'C.A.P. I DESAGUADERO', 104, 65, '1.60', [48, 62, 82, 91, -101, 65, -47, 8]],
                                ['2210', 'C.A.P. II ACORA', 97, 25, '3.88', [64, 47, 65, 62, -10, 25, -31, 8]],
                                ['2211', 'C.A.P. I LARAQUERI', 49, 55, '0.89', [4, 2, 6, 18, 55, -51, 6, 3]]
                            ];

                            foreach ($facilities as $index => $facility) {
                                echo "<tr>";
                                echo "<td class='text-center'>{$facility[0]}</td>";
                                echo "<td>{$facility[1]}</td>";
                                echo "<td class='text-center'><i class='icon-hospital'></i> {$facility[2]}</td>";
                                echo "<td class='text-center'><i class='icon-file'></i> {$facility[3]}</td>";
                                echo "<td class='text-center cobertura'>{$facility[4]}</td>";

                                foreach ($facility[5] as $value) {
                                    $class = $value > 0 ? 'valor-positivo' : ($value < 0 ? 'valor-negativo' : '');
                                    echo "<td class='text-center $class'>$value</td>";
                                }

                                // Remaining months of 2025 with zeros
                                for ($i = 0; $i < 8; $i++) {
                                    echo "<td class='text-center'>0</td>";
                                }
                                echo "</tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const rows = document.querySelectorAll('.table tbody tr');
            rows.forEach(row => {
                row.addEventListener('click', function() {
                    // Remove selected-row class from all rows
                    rows.forEach(r => r.classList.remove('selected-row'));
                    // Add selected-row class to the clicked row
                    this.classList.add('selected-row');
                });
            });
        });
    </script>
</body>

</html>