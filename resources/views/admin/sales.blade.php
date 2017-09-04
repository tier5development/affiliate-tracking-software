@extends('layouts.main')

@section('title')
    All sales
@endsection

@section('style')
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/1.4.1/css/buttons.dataTables.min.css">
@endsection


@section('content')
    <div class="content-wrapper">
        <section class="content">
            <div class="row">
                <div class="col-md-12">
                    <div class="panel panel-default panel-info">
                        <div class="panel-heading">
                            <div class="row">
                                <div class=" col-md-10 pull-left">
                                    <h4>All Sales</h4>
                                </div>
                            </div>
                            <div class="row">
                                @if(\Session::has('error'))
                                    <h4 style="color: red;">{{ \Session::get('error') }}</h4>
                                @endif
                            </div>
                        </div>
                        <div style="padding-bottom: 10px;"></div>
                        <div class="row">
                            <div class="col-md-12 col-sm-12 col-xs-12">
                                <label class="col-md-2 col-sm-2 col-xs-2">
                                    Filter by Campaign
                                </label>
                                <div class="col-md-4">
                                    <select class="form-control filterCampaign" name="campaign_id">
                                        <option value="0"> Select Campaign</option>
                                        @foreach($campaignDropDown as $campaign)
                                            <option value="{{ $campaign->id }}" {{ (isset($_GET['campaign']) && $_GET['campaign'] > 0 && $_GET['campaign'] == $campaign->id )?'selected':'' }}>{{ $campaign->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <label class="col-md-2 col-sm-2 col-xs-2">
                                    Filter by Affiliate
                                </label>
                                <div class="col-md-4">
                                    <select class="form-control filterAffiliate" name="affiliate_id">
                                        <option value="0"> Select Affiliate</option>
                                        @foreach($affiliateDropDown as $affiliate)
                                            <option value="{{ $affiliate->user->id }}" {{ (isset($_GET['affiliate']) && $_GET['affiliate'] > 0 && $_GET['affiliate'] == $affiliate->user->id )?'selected':'' }}>{{ $affiliate->user->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="panel-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered table-list">
                                    <thead>
                                    <tr>
                                        <th>Email</th>
                                        <th>Product Name</th>
                                        <th>Price</th>
                                        <th>Commission</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($sales as $sale)
                                            <tr>
                                                <td>{{ $sale['email'] }}</td>
                                                <td>{{ $sale['name'] }}</td>
                                                <td>{{ $sale['sale_price'] }}</td>
                                                <td>{{ $sale['commission'] }}</td>
                                                <td>
                                                    {{ ($sale['status']==2)?'Refunded':'sales' }}
                                                </td>
                                                <td>
                                                    @if($sale['status'] != 2)
                                                        <button type="button" class="btn btn-xs btn-warning refund" data-sales_id="{{ $sale['id'] }}">Refund</button>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection

@section('script')
    <script src="https://cdn.datatables.net/buttons/1.4.1/js/dataTables.buttons.min.js"></script>
    <script src="//cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
    <script src="//cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.32/pdfmake.min.js"></script>
    <script src="//cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.32/vfs_fonts.js"></script>
    <script src="//cdn.datatables.net/buttons/1.4.1/js/buttons.html5.min.js"></script>
    <script>
        $(function () {
            $('.table-list').DataTable({
                "paging": true,
                "lengthChange": false,
                "searching": true,
                "ordering": false,
                "info": true,
                "autoWidth": false
            });
            $('.filterAffiliate').on('change',function () {
                var affiliate = $(this).val();
                setGetParameter('affiliate',affiliate);
            });
            $('.filterCampaign').on('change',function () {
                var campaign = $(this).val();
                setGetParameter('campaign',campaign);
            });
            $('.refund').on('click',function () {
                var order_id =  $(this).data('sales_id');
                swal({
                    title: 'Are you sure?',
                    text: "You want to refund this!",
                    type: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, Refund it!'
                }).then(function () {
                    $.ajax({
                        url: "{{ route('sale.refund') }}",
                        type: "POST",
                        data: {
                            id: order_id,
                            _token: "{{ csrf_token() }}"
                        },
                        success: function (data) {
                            if (data.success) {
                                swal({
                                    title: "Success!",
                                    text: data.message,
                                    type: "success"
                                }).then( function(){
                                    window.location.reload();
                                },function (dismiss) {
                                    window.location.reload();
                                });
                            } else {
                                swal({
                                    title: "Error!",
                                    text: data.message,
                                    type: "error"
                                });
                            }
                        }
                    });
                })
            })
        });
    </script>
@endsection
