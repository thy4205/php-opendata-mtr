<head>
    <title>MTR</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta charset="utf-8">
    <link rel="stylesheet" type="text/css" href="css/bootstrap.min.css">
    <script src="js/bootstrap.min.js"></script>


    <script src="js/jquery-3.7.1.min.js"></script>
    <?php
    $line =  isset($_GET['line']) ? $_GET['line'] : "TML";
    $station =  isset($_GET['sta']) ? $_GET['sta'] : "TUM";


    ob_start();
    ?>
    <script>
        const line = '<?= $line ?>';
        const station = '<?= $station ?>';
    </script>
    <?php
    print ob_get_clean();
    ?>
</head>

<body>
    <div class="container p-3">
        <h1 class="display-3">港鐵月台資訊顯示</h1>
        <h4 id="stationName"></h4>
        <div id="mtr_time">
        </div>

        <?php
        $url = 'https://res.data.gov.hk/api/get-download-file?name=https%3A%2F%2Fopendata.mtr.com.hk%2Fdata%2Fmtr_lines_and_stations.csv';
        $url = 'mtr_lines_and_stations.csv';

        // Fetch the CSV content
        $csvContent = file_get_contents($url);
        if ($csvContent !== false) {
            // Parse the CSV content into an array
            $rows = array_map('str_getcsv', explode("\n", $csvContent));
            $headers = $rows[0];
            array_shift($rows);
            $assocRows = [];
            //print_r($headers);
            foreach ($rows as $row) {
                //print_r(count($row));
                if (count($row) == 7) {
                    $assocRows[] = array_combine($headers, $row);
                }
            }
            // Print the array
            //var_dump($assocRows);
        } else {
            echo "Failed to load the CSV file.";
        }
        ?>
    </div>

</body>
<script>
    const mtrStationsData = <?= json_encode($assocRows); ?>;
    const routeData = <?= (file_get_contents("routes.json")); ?>;

    jQuery(function() {
        var lineName = [];

        mtrStationsData.forEach(function(element) {
            if (lineName.includes(element["\ufeff\"Line Code\""]) == false) {
                lineName.push(element["\ufeff\"Line Code\""]);
            }
        });
        console.log(lineName);
        jQuery("#mtr_time").append(
            jQuery("<select>", {
                id: "route",
                name: "route",
                class: "form-select"
            })
        );
        jQuery("#mtr_time").append(
            jQuery("<select>", {
                id: "stations",
                name: "stations",
                class: "form-select"

            })
        );
        jQuery("#route").append($("<option>", {
            value: ""
        }).append("Please Select"));
        lineName.forEach(function(element) {
            jQuery("#route").append(
                jQuery("<option>", {
                    value: element
                }).text(routeData.routes.find(r => r.short == element)['nameChi'])
            );
        });

        <?php if (isset($_GET['line'])) { ?>
            jQuery("#route").val("<?= $_GET['line']; ?>");
        <?php
        } ?>


        jQuery("#route").change(function() {
            var route = jQuery(this).val();
            var stationName = [];
            mtrStationsData.forEach(function(element) {
                const stationExists = stationName.some(
                    (e) => e["value"] === element["Station Code"]
                );
                if (element["\ufeff\"Line Code\""] === route && !stationExists) {
                    stationName.push({
                        value: element["Station Code"],
                        text: element["Chinese Name"],
                    });
                }
            });
            jQuery("#stations").empty();
            jQuery("#stations").append(
                jQuery($("<option>", {
                    value: ""
                }).append("Please Select"))
            );
            stationName.forEach(function(station) {
                jQuery("#stations").append(
                    jQuery("<option>", {
                        value: station["value"]
                    }).text(station["text"])
                );
            });

            // window.location.href = window.location.pathname + "?" + jQuery.param({
            //     line: jQuery("#route").val(),
            //     sta: jQuery("#stations").val()
            // })

            console.log(jQuery("#stations").val());



        });
        $("#stations").change(function() {
            mtr({
                'line': jQuery("#route").val(),
                "sta": jQuery("#stations").val()
            });
            history.pushState(
                "",
                "",
                "?" + jQuery.param({
                    line: jQuery("#route").val(),
                    sta: jQuery("#stations").val()
                }));

            console.log(stationName);
        })
    });

    function findStationByCode(stationCode) {
        const station = mtrStationsData.find(
            (s) => s["Station Code"] === stationCode
        );
        if (station) {
            return {
                EnglishName: station["English Name"],
                ChineseName: station["Chinese Name"],
            };
        } else {
            return null; // Station not found
        }
    }

    function mtr(obj) {
        jQuery(".mtr").remove();

        jQuery.ajax({
            url: "https://rt.data.gov.hk/v1/transport/mtr/getSchedule.php",
            data: {
                line: obj["line"],
                sta: obj["sta"]
            },
            method: "GET",
            success: function(data) {

                jQuery("#stationName").text(findStationByCode(obj['sta'])["ChineseName"]);
                console.log(obj);
                console.log(data);
                console.log(data["data"][obj['line'] + "-" + obj['sta']]);

                var checkDir = [];

                if (data["data"][obj['line'] + "-" + obj['sta']]["UP"]) {
                    checkDir.push("UP");
                }
                if (data["data"][obj['line'] + "-" + obj['sta']]["DOWN"]) {
                    checkDir.push("DOWN");
                }
                runDirection = {
                    UP: {
                        name: "上行",
                    },
                    DOWN: {
                        name: "下行",
                    },
                };
                console.log(checkDir);

                checkDir.forEach((Direction) => {
                    console.log(Direction);
                    var upTable = jQuery("<div>", {
                        class: "table mtr"
                    });

                    //data['data'][line + '-' + station][Direction].forEach(element => {
                    data["data"][obj['line'] + "-" + obj['sta']][Direction].forEach((element) => {
                        var upTableRow = jQuery("<div>", {
                            class: "row align-items-center"
                        });

                        console.log(element);
                        // console.log(routeData.routes.find(r => r.short == $('#route').val())['colour']);

                        upTableRow.append(
                            jQuery("<div>", {
                                class: "col-2 col-sm-2 text-center"
                            }).html($("<h1>").css({
                                width: "1.5em",
                                background: routeData.routes.find(r => r.short == $('#route').val())['colour'],
                                color: "white",
                                "border-radius": "1em"
                            }).append(

                                element["plat"]
                            ))
                        );
                        upTableRow.append(
                            jQuery("<div>", {
                                class: "col-6"
                            }).append(
                                $("<p>", {
                                    class: "mb-0 p-0"
                                }).css({
                                    "font-size": "2rem"
                                }).append(findStationByCode(element["dest"])["ChineseName"])
                            ).append(
                                $("<p>", {
                                    class: "mb-0 p-0"
                                }).css({
                                    "font-size": "1.2rem"
                                }).append(findStationByCode(element["dest"])["EnglishName"])
                            ));
                        /* upTableRow.append(
                            jQuery("<div>", {
                                class: "col-2 eng"
                            }).text(
                                findStationByCode(element["dest"])["EnglishName"]
                            )
                        ); */
                        upTableRow.append(
                            jQuery("<div>", {
                                class: "col-4 text-end chi"
                            }).text(
                                element["ttnt"] + "分鐘 Min"
                            )
                        );
                        /*  upTableRow.append(
                             jQuery("<div>", {
                                 class: "col-2 eng"
                             }).text(
                                 element["ttnt"] + " Min"
                             )
                         ); */



                        upTableRow.appendTo(upTable);
                    });
                    var mtrDiv = jQuery("<div>", {
                        class: "mtr"
                    });
                    /*   mtr.append(
                          jQuery("<h2>").text(findStationByCode(station)["ChineseName"])
                      ); */
                    mtrDiv.append(jQuery("<h3>").text(runDirection[Direction]["name"]));
                    jQuery("#mtr_time").append(mtrDiv.append(upTable));
                });
            },
        });
    }
    <?php if (isset($_GET['line']) && isset($_GET['sta']) && !empty($_GET['line']) && !empty($_GET['sta'])) { ?>
        jQuery(function() {
            mtr({
                'line': "<?= $_GET['line']; ?>",
                "sta": "<?= $_GET['sta']; ?>"
            });
        });
    <?php }; ?>
</script>