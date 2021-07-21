<!DOCTYPE html>
<html>
<style>
    .tbl {background-color:#000;}
    .tbl td,th,caption{background-color:#fff}
    .tbl td {
        padding-left: 20px;
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
                    <td><p>{{$proposal->short_description }}</p></td>
                </tr>
                <tr>
                    <td><span style="font-weight: 600;">Explanation Benefit: </span></td>
                    <td><p>{{$proposal->explanation_benefit }}</p></td>
                </tr>
                <!-- <tr>
                    <td><span style="font-weight: 600;">Explanation Goal: </span></td>
                    <td><p>{{$proposal->explanation_goal }}</p></td>
                </tr> -->
                <tr>
                    <td><span style="font-weight: 600;">Total Grant: </span></td>
                    <td><p>{{$proposal->total_grant }}</p></td>
                </tr>
                <tr>
                    <td><span style="font-weight: 600;">License: </span></td>
                    <td><p>{{$proposal->license }}</p></td>
                </tr>
                <tr>
                    <td><span style="font-weight: 600;">Resume: </span></td>
                    <td><p>{{$proposal->resume }}</p></td>
                </tr>
                <tr>
                    <td><span style="font-weight: 600;">Extra Notes: </span></td>
                    <td><p>{{$proposal->extra_notes }}</p></td>
                </tr>
                <tr>
                    <td><span style="font-weight: 600;">License Other: </span></td>
                    <td><p>{{$proposal->license_other }}</p></td>
                </tr>
                <tr>
                    <td><span style="font-weight: 600;">Relationship: </span></td>
                    <td><p>{{$proposal->relationship }}</p></td>
                </tr>
                <tr>
                    <td><span style="font-weight: 600;">Received Grant Before: </span></td>
                    <td><p>{{$proposal->received_grant_before }}</p></td>
                </tr>
                <tr>
                    <td><span style="font-weight: 600;">Grant Id: </span></td>
                    <td><p>{{$proposal->grant_id }}</p></td>
                </tr>grant_id;
                <tr>
                    <td><span style="font-weight: 600;">Has Fulfilled: </span></td>
                    <td><p>{{$proposal->has_fulfilled }}</p></td>
                </tr>
                <tr>
                    <td><span style="font-weight: 600;">Previous Work: </span></td>
                    <td><p>{{$proposal->previous_work }}</p></td>
                </tr>
                <tr>
                    <td><span style="font-weight: 600;">Other Work: </span></td>
                    <td><p>{{$proposal->other_work }}</p></td>
                </tr>
                <!-- <tr>
                    <td><span style="font-weight: 600;">Received Grant: </span></td>
                    <td><p>{{$proposal->received_grant }}</p></td>
                </tr>
                <tr>
                    <td><span style="font-weight: 600;">Foundational Work: </span></td>
                    <td><p>{{$proposal->foundational_work }}</p></td>
                </tr> -->
                <tr>
                    <td><span style="font-weight: 600;">User Id: </span></td>
                    <td><p>{{$proposal->user_id }}</p></td>
                </tr>
                <tr>
                    <td><span style="font-weight: 600;">Include Membership: </span></td>
                    <td><p>{{$proposal->include_membership }}</p></td>
                </tr>
                <tr>
                    <td><span style="font-weight: 600;">Member Reason: </span></td>
                    <td><p>{{$proposal->member_reason }}</p></td>
                </tr>
                <tr>
                    <td><span style="font-weight: 600;">Member Benefit: </span></td>
                    <td><p>{{$proposal->member_benefit }}</p></td>
                </tr>
                <tr>
                    <td><span style="font-weight: 600;">Linkedin: </span></td>
                    <td><p>{{$proposal->linkedin }}</p></td>
                </tr>
                <tr>
                    <td><span style="font-weight: 600;">Github: </span></td>
                    <td><p>{{$proposal->github }}</p></td>
                </tr>
                <tr>
                    <td><span style="font-weight: 600;">Sponsor Code Id: </span></td>
                    <td><p>{{$proposal->sponsor_code_id }}</p></td>
                </tr>
                <!-- <tr>
                    <td><span style="font-weight: 600;">Yes No 1: </span></td>
                    <td><p>{{$proposal->yesNo1 }}</p></td>
                </tr>
                <tr>
                    <td><span style="font-weight: 600;">Yes No 2: </span></td>
                    <td><p>{{$proposal->yesNo2 }}</p></td>
                </tr>
                <tr>
                    <td><span style="font-weight: 600;">Yes No 3: </span></td>
                    <td><p>{{$proposal->yesNo3 }}</p></td>
                </tr>
                <tr>
                    <td><span style="font-weight: 600;">Yes No 4: </span></td>
                    <td><p>{{$proposal->yesNo4 }}</p></td>
                </tr>
                <tr>
                    <td><span style="font-weight: 600;">Yes No 1 Exp : </span></td>
                    <td><p>{{$proposal->yesNo1Exp }}</p></td>
                </tr>
                <tr>
                    <td><span style="font-weight: 600;">Yes No 2 Exp: </span></td>
                    <td><p>{{$proposal->yesNo2Exp }}</p></td>
                </tr>
                <tr>
                    <td><span style="font-weight: 600;">Yes No 3 Exp: </span></td>
                    <td><p>{{$proposal->yesNo3Exp }}</p></td>
                </tr>
                <tr>
                    <td><span style="font-weight: 600;">Yes No 4 Exp: </span></td>
                    <td><p>{{$proposal->yesNo4Exp }}</p></td>
                </tr> -->
                <tr>
                    <td><span style="font-weight: 600;">Form Field 1: </span></td>
                    <td><p>{{$proposal->formField1 }}</p></td>
                </tr>
                <tr>
                    <td><span style="font-weight: 600;">Form Field 2: </span></td>
                    <td><p>{{$proposal->formField2 }}</p></td>
                </tr>
                <tr>
                    <td><span style="font-weight: 600;">Purpose: </span></td>
                    <td><p>{{$proposal->purpose }}</p></td>
                </tr>
                <tr>
                    <td><span style="font-weight: 600;">Purpose Other: </span></td>
                    <td><p>{{$proposal->purposeOther }}</p></td>
                </tr>
                <tr>
                    <td><span style="font-weight: 600;">Tags: </span></td>
                    <td><p>{{$proposal->tags }}</p></td>
                </tr>
                <tr>
                    <td><span style="font-weight: 600;">Member Required: </span></td>
                    <td><p>{{$proposal->member_required }}</p></td>
                </tr>
                <tr>
                    <td><span style="font-weight: 600;">Company Or Organization: </span></td>
                    <td><p>{{$proposal->is_company_or_organization }}</p></td>
                </tr>
                <tr>
                    <td><span style="font-weight: 600;">Name Entity: </span></td>
                    <td><p>{{$proposal->name_entity }}</p></td>
                </tr>
                <tr>
                    <td><span style="font-weight: 600;">Entity Country: </span></td>
                    <td><p>{{$proposal->entity_country }}</p></td>
                </tr>
                <tr>
                    <td><span style="font-weight: 600;">Have Mentor: </span></td>
                    <td><p>{{$proposal->have_mentor }}</p></td>
                </tr>
                <tr>
                    <td><span style="font-weight: 600;">Name Mentor: </span></td>
                    <td><p>{{$proposal->name_mentor }}</p></td>
                </tr>
                <tr>
                    <td><span style="font-weight: 600;">Total Hours Mentor: </span></td>
                    <td><p>{{$proposal->total_hours_mentor }}</p></td>
                </tr>
                <tr>
                    <td><span style="font-weight: 600;">Agree 1: </span></td>
                    <td><p>{{$proposal->agree1 }}</p></td>
                </tr>
                <tr>
                    <td><span style="font-weight: 600;">Agree 2: </span></td>
                    <td><p>{{$proposal->agree2 }}</p></td>
                </tr>
                <tr>
                    <td><span style="font-weight: 600;">Agree 3: </span></td>
                    <td><p>{{$proposal->agree2 }}</p></td>
                </tr>

            </table>
        </div>
    </div>
</body>

</html>