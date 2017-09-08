@extends('layouts.main')

@section('title')
    Affiliate sales
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
                                    <h4>Paid Commissions Details</h4>
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
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="box box-success">
                                        <div class="box-header with-border">
                                            <h3 class="box-title">Payment Information</h3>
                                        </div>
                                        <!-- /.box-header -->
                                        <div class="box-body no-padding">
                                            <div class="col-md-4">
                                                <h2>Total: ${{ $netCommission }}</h2>
                                            </div>
                                            <div class="col-md-4">
                                                <h2>Paid: ${{ $totalPaid }}</h2>
                                            </div>
                                            <div class="col-md-4">
                                                <h2>Due:
                                                    ${{ round($netCommission - $totalPaid,2) }}</h2>
                                            </div>
                                        </div>
                                        <!-- /.box-body -->
                                    </div>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered table-list">
                                    <thead>
                                    <tr>
                                        <th>Campaign Name</th>
                                        <th>Affiliate Name</th>
                                        <th>Date</th>
                                        <th>Amount</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($commissions as $commission)
                                        <tr>
                                            <td>{{ $commission['campaign_name'] }}</td>
                                            <td>{{ $commission['affiliate_name'] }}</td>
                                            <td>{{ date('l jS \of F Y,  h:i:s A',strtotime($commission['date'])) }}</td>
                                            <td>${{ $commission['amount'] }}</td>
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
            $('.filterCampaign').on('change',function () {
                var campaign = $(this).val();
                setGetParameter('campaign',campaign);
            });
            $('.filterAffiliate').on('change',function () {
                var affiliate = $(this).val();
                setGetParameter('affiliate',affiliate);
            });
        });
    </script>
@endsection
