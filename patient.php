<?php

   require_once('server.php');
   require_once('index1.html');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>โปรแกรมส่งข้อมูลคนไข้ผ่าน Line</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/index.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <!-- <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin> -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.2/css/jquery.dataTables.min.css">
    <!-- <link href="https://fonts.googleapis.com/css2?family=Niramit:wght@500&display=swap" rel="stylesheet"> -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=K2D:wght@300&display=swap" rel="stylesheet">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.3/jquery.min.js"></script>
</head>

<style>
    .alert-date {
        /* font-size: large; */
        color: red;
    }
</style>

<body>
    <div class="container">
    <!-- action="sentdata.php" method="POST" -->
            <div class="row mt-2">
                <div class="heading mt-2 mb-2" role="alert" style="text-align:center">
                    <h3>คนไข้โรคกลุ่มเสี่ยงจิตเวช</h3>
                </div>
                <table id="table">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>VN</th>
                            <th>HN</th>
                            <th>ชื่อ - สกุล</th>
                            <!-- <th>ชื่อ</th>
                            <th>นามสกุล</th>  -->
                            <th>รหัสโรคหลัก</th>
                            <th>ชื่อรหัสโรค EN</th>
                            <th>ชื่อรหัสโรค TH</th>
                            <th>วันที่มารับบริการ</th>
                        </tr>
                    </thead>
                        <tbody>
                            <?php
                                
                               // $hosxp = $dbcon->query("SELECT ov.vn,ov.hn,pt.pname,pt.fname,pt.lname,ov.pdx, ov.dx0, ov.dx1, ov.dx2 ,ov.dx3, ov.dx4, ov.dx5,ov.vstdate
                               //                         FROM vn_stat ov
                               //                         left outer join patient pt on  pt.hn = ov.hn 
                               //                         where  ((ov.pdx  = 'T71') or (ov.dx0 = 'T71') or (ov.dx1 = 'T71') or (ov.dx2 = 'T71') or (ov.dx3 = 'T71') 
                               //                         or (ov.pdx like 'X70%') or (ov.dx0 like 'X70%') or (ov.dx1 like 'X70%') or (ov.dx2 like 'X70%') or (ov.dx3 like 'X70%') or (ov.pdx like 'X60%') 
                               //                         or (ov.dx0 like 'X60%') or (ov.dx1 like 'X60%') or (ov.dx2 like 'X60%') or (ov.dx3 like 'X60%') 
                               //                         or (ov.dx0 like 'X84%') or (ov.dx1 like 'X84%') or (ov.dx2 like 'X84%') or (ov.dx3 like 'X84%') or (ov.pdx like 'X84%'))");
                                                        
                                $hosxp = $dbcon->query("SELECT
                                ov.vn,
                                ov.hn,
                                concat( pt.pname, pt.fname, ' ', pt.lname ) AS fullname,
                                ov.vstdate,
                                ov.pdx,
                                ic1.NAME AS 'pdxname',
                                ic1.tname AS 'thainame' 
                            FROM
                                vn_stat ov
                                LEFT OUTER JOIN patient pt ON pt.hn = ov.hn
                                LEFT OUTER JOIN icd101 ic1 ON ic1.CODE = ov.pdx 
                            WHERE
                                ov.vstdate BETWEEN '2025-06-01' AND  CURDATE()
                                AND ((
                                        ov.pdx = 'T71' 
                                        ) 
                                    OR ( ov.dx0 = 'T71' ) 
                                    OR ( ov.dx1 = 'T71' ) 
                                    OR ( ov.dx2 = 'T71' ) 
                                    OR ( ov.dx3 = 'T71' ) 
                                    OR ( ov.pdx LIKE 'X70%' ) 
                                    OR ( ov.dx0 LIKE 'X70%' ) 
                                    OR ( ov.dx1 LIKE 'X70%' ) 
                                    OR ( ov.dx2 LIKE 'X70%' ) 
                                    OR ( ov.dx3 LIKE 'X70%' ) 
                                    OR ( ov.pdx LIKE 'X60%' ) 
                                    OR ( ov.dx0 LIKE 'X60%' ) 
                                    OR ( ov.dx1 LIKE 'X60%' ) 
                                    OR ( ov.dx2 LIKE 'X60%' ) 
                                    OR ( ov.dx3 LIKE 'X60%' ) 
                                    OR ( ov.dx0 LIKE 'X84%' ) 
                                    OR ( ov.dx1 LIKE 'X84%' ) 
                                    OR ( ov.dx2 LIKE 'X84%' ) 
                                    OR ( ov.dx3 LIKE 'X84%' ) 
                                OR ( ov.pdx LIKE 'X84%' )) 
                            ORDER BY
                                ov.vstdate DESC 
                                LIMIT 2"); 
                                $hosxp->execute();
                                $user = $hosxp->fetchAll();
                                for ($x = 0 ; $x < count($user) ; $x++) {
                                    $vn = "'".$user[$x]['vn']."'";
                                    $hn = "'".$user[$x]['hn']."'";
                                    $fullname = "'".$user[$x]['fullname']."'";
                                    $pdx = "'".$user[$x]['pdx']."'";
                                    $ic1 = "'".$user[$x]['pdxname']."'";
                                    $ic2 = "'".$user[$x]['thainame']."'";
                                    $vstdate = "'".$user[$x]['vstdate']."'";      
                            ?>
                                <tr >
                                    <td>
                                        <button class="btn btn-success btn-lg" onclick="Sentdata(<?php echo $user[$x]['vn'] ?>, <?php echo $hn ?>,
                                        <?php echo $fullname ?>, <?php echo $pdx ?>,<?php echo $ic1 ?>,<?php echo $ic2 ?>,<?php echo $vstdate ?>)">
                                        <i class='fab fa-line' style='font-size:36px;'></i></button>
                                    </td> 
                                    <td><?php echo $user[$x]['vn'] ?></td>
                                    <td><?php echo $user[$x]['hn'] ?></td>
                                    <td><?php echo $user[$x]['fullname'] ?></td>
                                    <td><?php echo $user[$x]['pdx'] ?></td>
                                    <td><?php echo $user[$x]['pdxname'] ?></td>
                                    <td><?php echo $user[$x]['thainame']?></td>
                                    <td class="alert-date"><?php echo $user[$x]['vstdate'] ?></td>
                                </tr>
                            <?php
                                }
                            ?>   
                        </tbody>
                </table>
                <div class="bt mb-3">
                    <button type="button" class="btn btn-success btn-lg" data-bs-toggle="modal" data-bs-target="#exampleModal" data-bs-whatever="@fat">เลือกข้อมูลจัดส่ง</button> 
                    <button type="button" class="btn btn-danger">Download PDF<i class="fa fa-download" style="font-size:24px"></i></button>
                </div> 
            </div>       
    </div>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.3.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.2/js/jquery.dataTables.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script>
        $(document).ready( function () {
            $('#table').DataTable();
        } );

        function Sentdata(vn,hn, fullname , pdx, ic1, ic2, vstdate) {
            let body = {
                vn: vn,
                hn: hn,
                fullname: fullname,
                pdx: pdx,
                ic1: ic1,
                ic2: ic2,
                vstdate: vstdate,
            }
            $(document).ready(function() {
                Swal.fire({
                title: 'success',
                text: 'ดำเนินการส่งข้อมูลเรียบร้อยแล้ว !!',
                icon: 'success',
                timer: 2500,
                showConfirmButton: false
            }).then((result)=>{
                if(result.isDismissed){
                    window.location.href='patient.php'
                }
            })
                }) 

            $.ajax({
                url: 'sentpt.php',
                type: "POST",
                data: body,
                success: function(data) {
                    console.log(data);
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.log(jqXHR);
                    console.log(textStatus);
                    console.log(errorThrown);
                }
            })
            
        }

    </script>
</body>
</html>