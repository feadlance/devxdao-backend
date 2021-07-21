<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::get('/test-email', 'AdminController@testEmail');
Route::get('/test-stripe', 'UserController@testStripe');
Route::get('/test-job', 'UserController@testJob');
Route::get('/pre-register-user', 'SharedController@getPreRegisterUser');
Route::get('/shared/all-proposals-2', 'SharedController@getAllProposals2');
Route::get('/shared/all-proposals-2/{proposalId}', 'SharedController@getDeatilProposal2');
Route::get('/shared/public/proposals/{proposalId}/changes', 'SharedController@getPublicProposalChanges');
Route::get('/shared/public/global-settings', 'SharedController@getGlobalSettings');

// Webhook
Route::post('/hellosign', 'SharedController@hellosignHook');

Route::post('/csv', 'APIController@downloadCSV');
Route::post('/login', 'APIController@login');
Route::post('/register', 'APIController@register');
Route::post('/pre-register', 'APIController@registerPre');
Route::post('/start-guest', 'APIController@startGuest');
Route::post('/send-reset-email', 'APIController@sendResetEmail');
Route::post('/reset-password', 'APIController@resetPassword');

Route::group(['middleware' => ['auth:api']], function() {
	// GET
	Route::get('/me', 'APIController@getMe');

	// POST
	Route::post('/verify-code', 'APIController@verifyCode');
	Route::post('/complete-step-review2', 'APIController@completeStepReview2');
	Route::post('/resend-code', 'APIController@resendCode');
});

Route::group(['prefix' => 'shared', 'middleware' => ['auth:api']], function() {
	// POST
	Route::post('/proposal/upload', 'SharedController@uploadProposalFiles');
	Route::post('/informal-voting', 'SharedController@startInformalVoting');
	Route::post('/formal-voting', 'SharedController@startFormalVoting');
	Route::post('/restart-voting', 'SharedController@restartVoting');
	Route::post('/change-password', 'SharedController@changePassword');
	Route::post('/generate-2fa', 'SharedController@generate2FA');
	Route::post('/check-2fa', 'SharedController@check2FA');
	Route::post('/check-proposal', 'SharedController@checkProposal');
	Route::post('/check-login-2fa', 'SharedController@checkLogin2FA');
	Route::post('/enable-2fa-login', 'SharedController@enable2FALogin');
	Route::post('/disable-2fa-login', 'SharedController@disable2FALogin');
	
	// PUT
	Route::put('/proposal/{proposalId}', 'SharedController@updateProposal');
	Route::put('/simple-proposal/{proposalId}', 'SharedController@updateSimpleProposal');
	Route::put('/proposal/{proposalId}/withdraw', 'SharedController@withdrawProposal');
	Route::put('/proposal/{proposalId}/force-withdraw', 'SharedController@forceWithdrawProposal');
	Route::put('/profile', 'SharedController@updateProfile');
	Route::put('/profile-info', 'SharedController@updateProfileInfo');
	Route::put('/account-info', 'SharedController@updateAccountInfo');
	
	// GET
	Route::get('/completed-votes', 'SharedController@getCompletedVotes');
	Route::get('/active-informal-votes', 'SharedController@getActiveInformalVotes');
	Route::get('/active-formal-votes', 'SharedController@getActiveFormalVotes');
	Route::get('/global-settings', 'SharedController@getGlobalSettings');
	Route::get('/active-discussions', 'SharedController@getActiveDiscussions');
	Route::get('/completed-discussions', 'SharedController@getCompletedDiscussions');
	Route::get('/proposal/{proposalId}', 'SharedController@getSingleProposal');
	Route::get('/proposal/{proposalId}/edit', 'SharedController@getSingleProposalEdit');
	Route::get('/proposal/{proposalId}/changes', 'SharedController@getProposalChanges');
	Route::get('/proposal/{proposalId}/change/{proposalChangeId}', 'SharedController@getSingleProposalChange');
	Route::get('/proposal/{proposalId}/change/{proposalChangeId}/comments', 'SharedController@getProposalChangeComments');
	Route::get('/pending-proposals', 'SharedController@getPendingProposals');
	Route::get('/active-proposals', 'SharedController@getActiveProposals');
	Route::get('/all-proposals', 'SharedController@getAllProposals');
	Route::get('/completed-proposals', 'SharedController@getCompletedProposals');
	Route::get('/grants', 'SharedController@getGrants');
});

// User Functions
Route::group(['prefix' => 'user', 'middleware' => ['auth:api']], function() {
	// POST
	Route::post('/force-approve-kyc', 'UserController@forceApproveKYC');
	Route::post('/force-deny-kyc', 'UserController@forceDenyKYC');
	Route::post('/milestone', 'UserController@submitMilestone');
	Route::post('/proposal', 'UserController@submitProposal');
	Route::post('/simple-proposal', 'UserController@submitSimpleProposal');
	Route::post('/proposal-change', 'UserController@submitProposalChange');
	Route::post('/proposal-change-comment', 'UserController@submitProposalChangeComment');
	Route::post('/vote', 'UserController@submitVote');
	Route::post('/shuftipro-temp', 'UserController@saveShuftiproTemp');
	Route::post('/hellosign-request', 'UserController@sendHellosignRequest');
	Route::post('/help', 'UserController@requestHelp');
	Route::post('/sponsor-code', 'UserController@createSponsorCode');
	Route::post('/check-sponsor-code', 'UserController@checkSponsorCode');
	Route::post('/associate-agreement', 'UserController@associateAgreement');
	Route::post('/press-dismiss', 'UserController@pressDismiss');
	Route::post('/check-active-grant', 'UserController@checkActiveGrant');
	Route::post('/proposal/{proposalId}/formal-milestone-voting', 'UserController@startFormalMilestoneVoting');

	// DELETE
	Route::delete('/sponsor-code/{codeId}', 'UserController@revokeSponsorCode');
	
	// PUT
	Route::put('/payment-proposal/{proposalId}', 'UserController@updatePaymentProposal');
	Route::put('/payment-proposal/{proposalId}/payment-intent', 'UserController@createPaymentIntent');
	Route::put('/payment-proposal/{proposalId}/stake-reputation', 'UserController@stakeReputation');
	Route::put('/payment-proposal/{proposalId}/stake-cc', 'UserController@stakeCC');
	Route::put('/proposal-change/{proposalChangeId}/support-up', 'UserController@supportUpProposalChange');
	Route::put('/proposal-change/{proposalChangeId}/support-down', 'UserController@supportDownProposalChange');
	Route::put('/proposal-change/{proposalChangeId}/approve', 'UserController@approveProposalChange');
	Route::put('/proposal-change/{proposalChangeId}/deny', 'UserController@denyProposalChange');
	Route::put('/proposal-change/{proposalChangeId}/withdraw', 'UserController@withdrawProposalChange');
	Route::put('/shuftipro-temp', 'UserController@updateShuftiproTemp');
	Route::put('/proposal/{proposalId}/payment-form', 'UserController@updatePaymentForm');

	// GET
	Route::get('/reputation-track', 'UserController@getReputationTrack');
	Route::get('/active-proposals', 'UserController@getActiveProposals');
	Route::get('/onboardings', 'UserController@getOnboardings');
	Route::get('/my-pending-proposals', 'UserController@getMyPendingProposals');
	Route::get('/my-active-proposals', 'UserController@getMyActiveProposals');
	Route::get('/my-payment-proposals', 'UserController@getMyPaymentProposals');
	Route::get('/active-proposal/{proposalId}', 'UserController@getActiveProposalById'); // Merged
	Route::get('/my-denied-proposal/{proposalId}', 'UserController@getMyDeniedProposalById');
	Route::get('/sponsor-codes', 'UserController@getSponsorCodes');
});

// Admin Functions
Route::group(['prefix' => 'admin', 'middleware' => ['auth:api']], function() {
	// GET
	Route::get('/emailer-data', 'AdminController@getEmailerData');
	Route::get('/pending-users', 'AdminController@getPendingUsers');
	Route::get('/pre-register-users', 'AdminController@getPreRegisterUsers');
	Route::get('/users', 'AdminController@getUsers');
	Route::get('/user/{userId}', 'AdminController@getSingleUser');
	Route::get('/user/{userId}/proposals', 'AdminController@getProposalsByUser');
	Route::get('/user/{userId}/votes', 'AdminController@getVotesByUser');
	Route::get('/user/{userId}/reputation', 'AdminController@getReputationByUser');
	Route::get('/pending-actions', 'AdminController@getPendingActions');
	Route::get('/proposal/{proposalId}', 'AdminController@getProposalById'); // Merged
	Route::get('/pending-grant-onboardings', 'AdminController@getPendingGrantOnboardings');
	Route::get('/move-to-formal-votes', 'AdminController@getMoveToFormalVotes');
	Route::get('/grant/{grantId}/file-url', 'AdminController@getUrlFileHellosignGrant');
	Route::get('/vote/{id}/user-not-vote', 'AdminController@getListUserNotVote');
	Route::get('/metrics ', 'AdminController@getMetrics');
	
	// POST
	Route::post('/formal-voting', 'AdminController@startFormalVoting');
	Route::post('/formal-milestone-voting', 'AdminController@startFormalMilestoneVoting');
	Route::post('/reset-user-password', 'AdminController@resetUserPassword');
	Route::post('/change-user-type', 'AdminController@changeUserType');
	Route::post('/add-reputation', 'AdminController@addReputation');
	Route::post('/subtract-reputation', 'AdminController@subtractReputation');
	Route::post('/add-emailer-admin', 'AdminController@addEmailerAdmin');
	Route::post('/grant/{grantId}/activate', 'AdminController@activateGrant');
	Route::post('/grant/{grantId}/begin', 'AdminController@beginGrant');
	Route::post('/grant/{grantId}/resend', 'AdminController@resendHellosignGrant');
	Route::post('/grant/{grantId}/remind', 'AdminController@remindHellosignGrant');
	
	// DELETE
	Route::delete('/emailer-admin/{adminId}', 'AdminController@deleteEmailerAdmin');

	// PUT
	Route::put('/emailer-trigger-admin/{recordId}', 'AdminController@updateEmailerTriggerAdmin');
	Route::put('/emailer-trigger-user/{recordId}', 'AdminController@updateEmailerTriggerUser');
	Route::put('/emailer-trigger-member/{recordId}', 'AdminController@updateEmailerTriggerMember');
	
	Route::put('/global-settings', 'AdminController@updateGlobalSettings');
	
	Route::put('/participant/{userId}/approve-request', 'AdminController@approveParticipantRequest');
	Route::put('/participant/{userId}/deny-request', 'AdminController@denyParticipantRequest');
	Route::put('/participant/{userId}/revoke', 'AdminController@revokeParticipant');
	Route::put('/participant/{userId}/activate', 'AdminController@activateParticipant');
	Route::put('/participant/{userId}/deny', 'AdminController@denyParticipant');
	
	Route::put('/pre-register/{recordId}/approve', 'AdminController@approvePreRegister');
	Route::put('/pre-register/{recordId}/deny', 'AdminController@denyPreRegister');
	
	Route::put('/user/{userId}/allow-access', 'AdminController@allowAccessUser');
	Route::put('/user/{userId}/deny-access', 'AdminController@denyAccessUser');
	
	Route::put('/user/{userId}/ban', 'AdminController@banUser');
	Route::put('/user/{userId}/unban', 'AdminController@unbanUser');
	Route::put('/user/{userId}/approve-kyc', 'AdminController@approveKYC');
	Route::put('/user/{userId}/deny-kyc', 'AdminController@denyKYC');
	Route::put('/user/{userId}/reset-kyc', 'AdminController@resetKYC');
	
	Route::put('/proposal/{proposalId}/approve', 'AdminController@approveProposal');
	Route::put('/proposal/{proposalId}/deny', 'AdminController@denyProposal');
	Route::put('/proposal/{proposalId}/approve-payment', 'AdminController@approveProposalPayment');
	Route::put('/proposal/{proposalId}/deny-payment', 'AdminController@denyProposalPayment');
	Route::put('/proposal-change/{proposalChangeId}/force-approve', 'AdminController@forceApproveProposalChange');
	Route::put('/proposal-change/{proposalChangeId}/force-deny', 'AdminController@forceDenyProposalChange');
	Route::put('/proposal-change/{proposalChangeId}/force-withdraw', 'AdminController@forceWithdrawProposalChange');
	Route::put('/user/{userId}/kyc-info', 'AdminController@updateKYCinfo');
});