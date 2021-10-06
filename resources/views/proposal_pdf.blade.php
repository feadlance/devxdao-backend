<!DOCTYPE html>
<html>
<style>
    * {
        font-size: 9px;
    }
    .tbl {background-color:#000; width: 100%;}
    .tbl td,th,caption{background-color:#fff}
    .tbl td {
        padding-left: 20px;
    }
    p {
        white-space: pre-wrap;
        word-wrap: break-word;
    }
</style>
<body>
    <div>
        <div class="content">
            <table cellspacing="1" class="tbl">
                <tr>
                    <td><span style="font-weight: 600;">Title: </span></td>
                    <td><p>{{$proposal->title}}</p></td>
                </tr>
                <tr>
                    <td><span style="font-weight: 600;">Short Description: </span></td>
                    <td><p>{!! \Illuminate\Support\Str::limit($proposal->short_description, 600) !!}</p></td>
                </tr>
                <tr>
                    <td><span style="font-weight: 600;">Explanation Benefit: </span></td>
                    <td><p>{!! $proposal->explanation_benefit !!}</p></td>
                </tr>
                <!-- <tr>
                    <td><span style="font-weight: 600;">Explanation Goal: </span></td>
                    <td><p>{{$proposal->explanation_goal }}</p></td>
                </tr> -->
                <tr>
                    <td><span style="font-weight: 600;">Total Grant: </span></td>
                    <td><p>{{$proposal->total_grant }}</p></td>
                </tr>
                <!-- <tr>
                    <td><span style="font-weight: 600;">License: </span></td>
                    <td><p>{{$proposal->license }}</p></td>
                </tr> -->
                <tr>
                    <td><span style="font-weight: 600;">Resume: </span></td>
                    <td><p>{!! $proposal->resume !!}</p></td>
                </tr>
                <tr>
                    <td><span style="font-weight: 600;">Extra Notes: </span></td>
                    <td><p>{!! $proposal->extra_notes !!}</p></td>
                </tr>
                <!-- <tr>
                    <td><span style="font-weight: 600;">License Other: </span></td>
                    <td><p>{{$proposal->license_other }}</p></td>
                </tr> -->
                <!-- <tr>
                    <td><span style="font-weight: 600;">Relationship: </span></td>
                    <td><p>{{$proposal->relationship }}</p></td>
                </tr> -->
                <tr>
                    <td><span style="font-weight: 600;">Received Grant Before: </span></td>
                    <td><p>{{$proposal->received_grant_before == 1 ? 'Yes' : 'No' }}</p></td>
                </tr>
                <tr>
                    <td><span style="font-weight: 600;">Grant Id: </span></td>
                    <td><p>{{$proposal->grant_id }}</p></td>
                </tr>
                <tr>
                    <td><span style="font-weight: 600;">Has Fulfilled: </span></td>
                    <td><p>{{$proposal->has_fulfilled == 1 ? 'Yes' : 'No' }}</p></td>
                </tr>
                <tr>
                    <td><span style="font-weight: 600;">User Id: </span></td>
                    <td><p>{{$proposal->user_id }}</p></td>
                </tr>
                <tr>
                    <td><span style="font-weight: 600;">Has Mentor: </span></td>
                    <td><p>{{$proposal->have_mentor == 1 ? 'Yes' : 'No' }}</p></td>
                </tr>
                <tr>
                    <td><span style="font-weight: 600;">Mentor Name: </span></td>
                    <td><p>{{$proposal->name_mentor }}</p></td>
                </tr>
                <tr>
                    <td><span style="font-weight: 600;">Mentor Hours: </span></td>
                    <td><p>{{$proposal->total_hours_mentor }}</p></td>
                </tr>

                <tr>
                    <td><span style="font-weight: 600;">Company or Organization: </span></td>
                    <td><p>{{$proposal->is_company_or_organization == 1 ? 'Yes' : 'No' }}</p></td>
                </tr>
                <tr>
                    <td><span style="font-weight: 600;">Entity Name: </span></td>
                    <td><p>{{$proposal->name_entity }}</p></td>
                </tr>
                <tr>
                    <td><span style="font-weight: 600;">Entity Country: </span></td>
                    <td><p>{{$proposal->entity_country }}</p></td>
                </tr>
                <tr>
                    <td><span style="font-weight: 600;">Team Members: </span></td>
                    <td>@foreach($proposal->teams as $key=>$team)
                        <p>Team Member {{$key + 1}} : {{$team->full_name }}</p>
                        @endforeach
                    </td>
                </tr>
                @foreach($proposal->milestones as $key=>$milestone)
                <tr>
                    <td><span style="font-weight: 600;">Milestone #{{$key + 1}}</span></td>
                    <td>
                        <p>Milestone title: {{$milestone->title}}</p>
                        <p>The portion that the OP is requesting from the total grant for the milestone: {{$milestone->grant}}</p>
                        <p>Due date: {{$milestone->deadline}}</p>
                        <p>Details of what will be delivered: </p><p>{!! $milestone->details !!}</p>
                        <p>Acceptance Criteria: </p><p>{!! $milestone->criteria !!}</p>
                    </td>
                </tr>
                @endforeach
            </table>
        </div>
    </div>
</body>

</html>
