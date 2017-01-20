{{ content() }}


<div id="locationlist">
    {{ content() }}

    <ul class="pager">
        <li class="pull-right">
            <a href="/admindashboard/create/<?=$this->view->agency_type_id?>/<?=$loggedUser->agency_id?>/<?=$agency->parent_id?>" class="btn red btn-outline">Create <?=($agency_type_id==1?'Agency':'Business')?></a>
        </li>
    </ul>
    <?php
if ($agencies) {
?>
    <div class="row">
        <div class="col-xs-12">
            <div class="portlet box red">
                <div class="portlet-title">
                    <div class="caption">
                        <i class="fa fa-globe"></i> <?=($agency_type_id==1?'Agency':'Business')?> List </div>
                    <div class="tools"> </div>
                </div>


                <div class="portlet-body">

                <div>

<?php if($this->session->has("err_msg")){
     $err="<font color='red'>".$this->session->get("err_msg")."</font>";
     echo $err;
     $this->session->set("err_msg","");
} ?>
</div>
                    <table id="basic-datatables" class="table table-striped table-bordered" cellspacing="0" width="100%">
                        <thead>
                        <tr>
                            <?php if($loggedUser->is_admin && $agency_type_id != 1) { ?>
                            <th>Agency</th>
                            <?php } ?>
                            <th>Name</th>
                            <th>Email Address</th>
                            <th>Date Created</th>
                            <th>Plan Name</th>
                            <th>Account Type</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php
                        
foreach($agencies as $agency) {
?><!--Business Name, Email Address, Date Created, Plan Name, Account Type (Free/Paid), Status (can turn on and off from here - Active/Inactive), Action -->
                        <tr>
                            <?php

                            if($loggedUser->is_admin && $agency_type_id != 1) {
                                    echo "<td>" . $tAllParentAgencies[$agency->parent_id]['name'] . "</td>";
                                }
                            ?>
                            <td><?=($agency->name) ? $agency->name : 'n/a'?></td>
                            <td><?=$agency->email?></td>
                            <td><?=date("Y-m-d",strtotime($agency->date_created))?></td>
                            <td><?=(isset($agency->subscription_id) && $agency->subscription_id > 0?$agency->subscription->name:'Free')?></td>
                            <td><?=(isset($agency->subscription_id) && $agency->subscription_id > 0?'Paid':($generate_array[$agency->id]=='FR')?'Free':"Paid")?></td>
                            <td><a href="/admindashboard/status/<?=$agency_type_id?>/<?=$agency->agency_id?>/<?=($agency->status==0?1:0)?>"><img src="/public/img/<?=($agency->status==0?'off':'on')?>.png" /></td>
                            <td style="text-align: right;">
                                <div class="actions">
                                    <div class="btn-group">
                                        <a data-toggle="dropdown" href="javascript:;" class="btn btn-sm green dropdown-toggle" aria-expanded="false"> Actions <i class="fa fa-angle-down"></i></a>
                                        <ul class="dropdown-menu pull-right">
                                            <li><a href="/admindashboard/view/<?=$agency_type_id?>/<?=$agency->agency_id?>" class=""><i class="icon-eye"></i> View</a></li>
                                            <li><a href="/admindashboard/edit/<?=$agency->agency_id?>" class=""><i class="icon-pencil"></i> Edit</a></li>
                                            <li><a href="/admindashboard/view/<?=$agency_type_id?>/<?=$agency->agency_id?>" class=""><i class="icon-user"></i> Password</a></li>
                                            <?php if($agency->parent_id != 0) { ?>
                                            	<li><a href="/admindashboard/delete/<?=$agency_type_id?>/<?=$agency->agency_id?>" onclick="return confirm('Are you sure you want to delete this item?');" class=""><i class="fa fa-trash-o"></i> Delete</a></li>
                                            <?php } ?>
                                            <li><a href="/admindashboard/view/<?=$agency_type_id?>/<?=$agency->agency_id?>" class=""><i class="icon-envelope"></i> Resend Credentials</a></li>
                                            <li><a href="/admindashboard/view/<?=$agency_type_id?>/<?=$agency->agency_id?>" class=""><i class="icon-paper-plane"></i> Manage</a></li>
                                        </ul>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php
}
?>
                        </tbody>
                    </table>


                </div>
            </div>
        </div>
    </div>

    <?php } else { ?>
    No agencies

    <?php }  ?>

</div>
<script>
    $(function(){
        $('tbody tr td.name').bind('click',function(e){
            e.stopPropagation();
           var edit_link = $(this).parent().find('i.icon-pencil').parent().attr('href');
            window.location = edit_link;
        });

    });

</script>
<style type="text/css">
    tbody tr td.name:hover{
        cursor:pointer;
    }
</style>
