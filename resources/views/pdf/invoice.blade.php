<!DOCTYPE html>
<html>
<style>
    * {
        font-size: 14px;
    }

    .page-break {
        page-break-after: always;
    }

    .tbl {
        background-color: #000;
        width: 100%;
    }

    .tbl td,
    th,
    caption {
        background-color: #fff
    }

    .tbl td {
        padding-left: 20px;
    }
</style>

<body>
    <div>
        <div class="content">
            <div>
                <h2>Payee Details</h2>
                <p> User Email: {{$invoice->user->email}} </p>
                <p> Name: {{$invoice->user->first_name}} {{$invoice->user->first_name}}</p>
                <p> Company: {{$invoice->user->profile->company}} </p>
                <p> Shufti Ref Number: {{$invoice->user->shuftipro->reference_id}} </p>
            </div>
            <div>
                <h2>Payment Details</h2>
                <p> Invoice number: {{$invoice->id}} </p>
                <p> Invoice date: {{ $vote->updated_at->format('d/m/Y H:i A') }} </p>
                <p> Grant Number: {{$invoice->proposal_id}} </p>
                <p> Grant Link: {{$invoice->public_grant_url}}</p>
                <p> Milestone Number: {{$invoice->milestone_number}} </p>
            </div>
            <div>
                <h2>Vote Details</h2>
                <p> Date Formal Vote Completed: {{ $vote->updated_at->format('d/m/Y') }} </p>
                <p> Vote Obtained: {{$vote->results->count()}}</p>
                <p> Result: PASS </p>
                <p> Stake For/Against: {{ $vote->results->where('type', 'for')->count() }} / {{ $vote->results->where('type', 'against')->count() }} </p>
            </div>
        </div>
        <div class="page-break"></div>

        <div class="content">
            <h2>Vote detail table.</h2>
            <table class="table">
                <thead>
                    <tr>
                        <th><strong> Forum Name </strong></th>
                        <th><strong> Stake For </strong></th>
                        <th><strong> Stake Against </strong></th>
                        <th><strong> Time Of Vote </strong></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($vote->results as $voteResults)
                    <tr>
                        <td> {{ $voteResults->forum_name }} </td>
                        <td> {{ $voteResults->type == 'for' ? $voteResults->value : '' }} </td>
                        <td> {{ $voteResults->type == 'against' ? $voteResults->value : '' }} </td>
                        <td> {{ $voteResults->created_at->format('m-d-Y  H:i A')}} </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</body>

</html>