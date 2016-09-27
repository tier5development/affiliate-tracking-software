<head>
    <style type="text/css">
        body {
            background: white;
        }

        .header_row {
            background-color: rgb(239,239,239);
        }

        .container {
            width: 1024px;
            border: 1px solid rgba(0,0,0,.04);
            margin-left: auto;
            margin-right: auto;
        }

        .header {
            width: 1024px;
            margin-left: auto;
            margin-right: auto;
        }

        .footer {
            width: 1024px;
            margin-left: auto;
            margin-right: auto;
            text-align: center;
        }

        .logo {
            float: left;
            display: block;
            max-height: 50px;
        }

        .title {
            float: right;
        }

        .employee_table {
            margin-top: 20px;
            margin-bottom: 20px;
        }

        .table_header {
            margin-left: 20px;
            margin-right: 20px;
            text-align: center;
        }

        .table_cell {
            text-align: center;
            font-size: 16px;
            font-family: "Open Sans";
            color: rgb(71, 71, 71);
            line-height: 1.25;
        }

        table {
            margin-left: auto;
            margin-right: auto;
        }

        tr {
            height: 60px;
        }

        td, th {
            text-align: center;
            padding-left: 25px;
            padding-right: 25px;
        }

        td.total_feedback, td.rank {
            width: 57px;
            /*font-size: 12px;*/
        }

        td.total_feedback, td.total_feedback {
            width: 57px;
            /*font-size: 12px;*/
        }

        td.average_satisfaction, td.average_satisfaction {
            width: 57px;
            /*font-size: 12px;*/
        }

        tbody > tr:nth-child(1) {
            font-size: 36px !important;
            height: 120px;
        }

        tbody > tr:nth-child(2) {
            font-size: 30px !important;
            height: 100px;
        }

        tbody > tr:nth-child(3) {
            height: 80px;
            font-size: 24px !important;
        }

        tr:nth-child(even) {
            background-color: rgb(239,239,239);
        }
        tr:nth-child(odd) {
            background-color: rgb(250,250,250);
        }
        th > tr {
            background-color: rgb(250,250,250);
        }
        td.employee, th.employee {
            width: 94px;
            font-size: 14px;
        }


    </style>
</head>
<body>
    <div class="header">
        <img class="logo" src="http://<?=$objBusiness->custom_domain; ?>.getmobilereviews.com/img/agency_logos/<?=$objBusiness->logo_path;?>" />
        <div class="title"><h3><?=$objBusiness->name; ?></h3></div>
    </div>
    <div style="clear: both;"></div>
    <div class="container">
        <div class="table_header">
            <h4><?=date('F'); ?> Employee Ranking Report<br />As Of <?=date("m/d/Y"); ?></h4><br />
        </div>
        <table class="employee_table">
            <thead>
                <tr>
                    <th>Rank</th>
                    <th class="employee">Employee</th>
                    <th class="total_feedback">Total Feedback</th>
                    <th class="total_feedback">Average Satisfaction</th>
                </tr>
            </thead>
            <tbody>
            <?php $Count = 1; ?>
            {% for Employee in dbEmployees %}
                <?php
                    switch($Count) {
                        case 1:
                            $Icon = "<img src='http://" . $objBusiness->custom_domain . ".getmobilereviews.com/img/gold_medal.png' />";
                            break;
                        case 2:
                                $Icon = "<img src='http://" . $objBusiness->custom_domain . ".getmobilereviews.com/img/silver_medal.png' />";
                            break;
                        case 3:
                                $Icon = "<img src='http://" . $objBusiness->custom_domain . ".getmobilereviews.com/img/bronze_medal.png' />";
                            break;
                        default:
                            // Fully aware this only works up to 110 employees.  GARY_TODO:  Fix this if it becomes an issue.
                            $Icon = "{$Count}";
                            if($Count > 20 && $Count % 10 == 1)
                                $Icon .= "st";
                            elseif($Count > 20 && $Count % 10 == 2)
                                $Icon .= "st";
                            elseif($Count > 20 && $Count % 10 == 3)
                                $Icon .= "st";
                            else
                                $Icon .= "th";
                            break;
                    }
                ?>

                <tr>
                    <td class="rank"><?=$Icon; ?></td>
                    <td class="employee"><?=$Employee->name; ?></td>
                    <td class="total_feedback"><?=$Employee->sms_sent_all_time ?: 0; ?></td>
                    <td class="average_satisfaction"><?=$Employee->avg_feedback ?: 0; ?></td>
                </tr>
                <?php $Count++; ?>
            {% endfor %}
            </tbody>
        </table>
    </div>
    <div class="footer">
        <p><b><?=$objBusiness->name; ?></b> | <a href="<?=$objBusiness->website; ?>"><?=$objBusiness->website; ?></a> | Like us on Facebook</p>
        <p><?=$objBusiness->address; ?> <?=$objBusiness->address2; ?>, <?=$objBusiness->state_province; ?>, <?=$objBusiness->postal_code; ?></p>
        <p><?=$objBusiness->phone; ?></p>
        <br />
        Powered by <?=$objAgency->name; ?>
    </div>
</body>