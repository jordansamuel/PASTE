<?php
/*
 * Language File: English
 *
 * Paste https://github.com/boxlabss/PASTE
 * demo: https://paste.boxlabs.uk/
 *
 * https://phpaste.sourceforge.io/
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 3
 * of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License in LICENCE for more details.
 */

$lang = array();

$lang['banned'] = "You have been banned from " . $site_name;
$lang['expired']        = "The paste you're looking for has expired.";
$lang['pleaseregister'] = "<br><br> <a class=\"btn btn-default\" href=\"login.php\">Login</a> or <a class=\"btn btn-default\" href=\"login.php?action=signup\">Register</a> to submit a new paste. It's free.";
$lang['registertoedit'] = "<a class=\"btn btn-default\" href=\"login.php\">Login</a> or <a class=\"btn btn-default\" href=\"login.php?action=signup\">Register</a> to edit or fork this paste. It's free.";
$lang['editpaste'] = "Edit";
$lang['forkpaste'] = "Fork";
$lang['guestmsgbody'] = "<a href=\"login.php\">Login</a> or <a href=\"login.php?action=signup\">Register</a> to edit, delete and keep track of your pastes and more.";
$lang['emptypastebin'] = "There are no pastes to show.";
$lang['siteprivate'] = "This pastebin is private. <a class=\"btn btn-default\" href=\"login.php\">Login</a>";
$lang['image_wrong'] = "Wrong captcha.";
$lang['missing-input-response'] = "The reCAPTCHA response parameter is missing. Please verify your PASTE settings.";
$lang['missing-input-secret'] = "The reCAPTCHA secret parameter is missing. Please add it to your PASTE settings.";
$lang['invalid-input-response'] = "The reCAPTCHA response parameter is invalid. Please try to complete the reCAPTCHA again.";
$lang['invalid-input-secret'] = "The reCAPTCHA secret parameter is invalid or malformed. Please double check your PASTE settings.";
$lang['empty_paste'] = "You cannot post an empty paste.";
$lang['large_paste'] = "Your paste is too large. Max size is " . $pastelimit . "MB";
$lang['paste_db_error'] = "Unable to post to database.";
$lang['error'] = "Something went wrong.";
$lang['archive'] = "Archive";
$lang['archives'] = "Pastes Archive";
$lang['archivestitle'] = "This page contains the most recently created 100 public pastes.";
$lang['contact'] = "Contact Us";
$lang['full_name'] = "Full Name";
$lang['email'] = "Email";
$lang['email_invalid'] = "Your email address seems to be invalid.";
$lang['message'] = "Your message is required.";
$lang['login/register'] = "Login or Register";
$lang['rememberme'] = "Keep me signed in.";
$lang['mail_acc_con'] = "$site_name Account Confirmation";
$lang['mail_suc'] = "Verification code successfully sent to your email address.";
$lang['email_ver'] = "Email already verified.";
$lang['email_not'] = "Email not found.";
$lang['pass_change'] = "Password changed successfully and sent to your email.";
$lang['notverified'] = "Account not verified.";
$lang['incorrect'] = "Incorrect User/Password";
$lang['missingfields'] = "All fields must be filled out.";
$lang['userexists'] = "Username already taken.";
$lang['emailexists'] = "Email already registered.";
$lang['registered'] = "Your account was successfully registered.";
$lang['usrinvalid'] = "Your username can only contain letters or numbers.";
$lang['mypastes'] = "My Pastes";
$lang['pastedeleted'] = "Paste deleted.";
$lang['databaseerror'] = "Unable to post to database.";
$lang['userchanged'] = "Username changed successfully.";
$lang['usernotvalid'] = "Username not valid.";
$lang['privatepaste'] = "This is a private paste.";
$lang['wrongpassword'] = "Wrong password.";
$lang['pwdprotected'] = "Password protected paste";
$lang['notfound'] = "404 Not Found";
$lang['wrongpwd'] = "Wrong password. Try again.";
$lang['myprofile'] = "My Profile";
$lang['profileerror'] = "Unable to update the profile information";
$lang['profileupdated'] = "Your profile information is updated";
$lang['oldpasswrong'] = "Your old password is wrong.";
$lang['pastetitle'] = "Paste Title";
$lang['pastetime'] = "Paste Time";
$lang['pastesyntax'] = "Paste Syntax";
$lang['pasteviews'] = "Paste Views";
$lang['wentwrong'] = "Something went wrong.";
$lang['versent'] = "A verification email has been sent to your email address.";
$lang['modpaste'] = "Modify or Fork";
$lang['newpaste'] = "New Paste";
$lang['highlighting'] = "Syntax Highlighting";
$lang['expiration'] = "Paste Expiration";
$lang['visibility'] = "Paste Visibility";
$lang['pwopt'] = "Password (Optional)";
$lang['encrypt'] = "All pastes are automatically encrypted with AES-256";
$lang['entercode'] = "Enter Code";
$lang['almostthere'] = "Almost there. One more step to go.";
$lang['username'] = "Username";
$lang['autogen'] = "Auto generated name";
$lang['setuser'] = "Set your Username";
$lang['keepuser'] = "Keep autogenerated name? You can change it once.";
$lang['enterpwd'] = "Enter the password";
$lang['totalpastes'] = "Total Pastes:";
$lang['membtype'] = "Membership Type:";
$lang['chgpwd'] = "Change Password";
$lang['curpwd'] = "Current Password";
$lang['newpwd'] = "New Password";
$lang['confpwd'] = "Confirm Password";
$lang['viewpastes'] = "View all my pastes";
$lang['recentpastes'] = "Recent Pastes";
$lang['user_public_pastes'] = "'s pastes";
$lang['yourpastes'] = "Your Pastes";
$lang['mypastestitle'] = "All of your pastes, in one place.";
$lang['delete'] = "Delete";
$lang['highlighted'] = "The text below is selected, press Ctrl+C to copy to your clipboard. (&#8984;+C on Mac)";
$lang['download'] = "Download";
$lang['showlineno'] = "Show/Hide line no.";
$lang['copyto'] = "Copy text to clipboard";
$lang['rawpaste'] = "Raw Paste";
$lang['membersince'] = "Joined: ";
$lang['delete_error_invalid'] = "Error: Paste not deleted because it does not exist or you do not own the paste.";
$lang['deleteaccount'] = 'Delete My Account';
$lang['deletewarn'] = 'This will permanently remove your account and all your pastes. This action cannot be undone.';
$lang['typedelete'] = 'Type DELETE to confirm.';
$lang['confirmdeletehint'] = 'You must type DELETE (all caps).';
$lang['cancel'] = 'Cancel';
$lang['confirmdelete'] = 'Confirm Delete';
$lang['wentwrong'] = 'Something went wrong.';
$lang['invalidtoken'] = 'Invalid CSRF token.';
$lang['not_logged_in'] = "Error: You must be logged in to do that.";
$lang['public'] = "Public";
$lang['unlisted'] = "Unlisted";
$lang['private'] = "Private";
$lang['hello'] = "Hello";
$lang['profile-message'] = "This is your profile page where you can manage your pastes. All of your public, private and unlisted pastes will be shown here. You can also delete your pastes from this page. If other users visit your page they will only see pastes you have set public.";
$lang['profile-stats'] = "Some of your statistics:";
$lang['profile-total-pastes'] = "Total Pastes:";
$lang['profile-total-pub'] = "Total public pastes:";
$lang['profile-total-unl'] = "Total unlisted pastes:";
$lang['profile-total-pri'] = "Total private pastes:";
$lang['profile-total-views'] = "Total views of all your pastes:";
$lang['embed-hosted-by'] = "hosted by";
$lang['view-raw'] = "View Raw";
$lang['my_account'] = "My Account";
$lang['guest'] = "Guest";
$lang['login'] = "Login";
$lang['signup'] = "Register";
$lang['forgot_password'] = "Forgot Password";
$lang['resend_verification'] = "Resend Verification Email";
$lang['or_login_with'] = "Or login with";
$lang['login_with_google'] = "Google";
$lang['login_with_facebook'] = "Facebook";
$lang['already_have_account'] = "Already have an account?";
$lang['reset_password'] = "Reset Password";
$lang['new_password'] = "New Password";
$lang['send_reset_link'] = "Send Reset Link";
$lang['email_verified'] = "Email verified successfully. You can now log in.";
$lang['invalid_code'] = "Invalid or expired code.";
$lang['pass_reset'] = "Password reset successful. You can now log in.";
$lang['mail_error'] = "Failed to send email.";
$lang['settings'] = "Settings";
$lang['logout'] = "Logout";
$lang['49'] = "49";
$lang['50'] = "50";
$lang['account_suspended'] = "Account Suspended";
$lang['ajax_error'] = "Ajax Error";
$lang['createpaste'] = "Create Paste";
$lang['email_not_verified'] = "Email Not Verified";
$lang['expired'] = "Expired";
$lang['forgot'] = "Forgot";
$lang['fullname'] = "Full name";
$lang['guestmsgtitle'] = "Hello!";
$lang['guestwelcome'] = "Guestwelcome";
$lang['invalid_credentials'] = "Invalid Credentials";
$lang['invalid_email'] = "Invalid Email";
$lang['invalid_reset_code'] = "Invalid Reset Code";
$lang['invalid_state'] = "Invalid State";
$lang['invalid_username'] = "Invalid Username";
$lang['login_required'] = "Login Required";
$lang['login_success'] = "Login Success";
$lang['low_score'] = "Low Score";
$lang['my-pastes'] = "My Pastes";
$lang['no_results'] = "No Results";
$lang['password'] = "Password";
$lang['password_reset_success'] = "Password Reset Success";
$lang['password_too_short'] = "Password Too Short";
$lang['pastemember'] = "Pastemember";
$lang['pastes'] = "Pastes";
$lang['recaptcha_error'] = "Recaptcha Error";
$lang['recaptcha_failed'] = "reCAPTCHA failed to verify you're not a bot. Refresh and try again.";
$lang['recaptcha_missing'] = "Recaptcha Missing";
$lang['recaptcha_timeout'] = "Recaptcha Timeout";
$lang['resend'] = "Resend";
$lang['search'] = "Search";
$lang['search_results_for'] = "Search Results for";
$lang['signup_success'] = "Signup Success";
$lang['sort'] = "Sort";
$lang['sort_code_asc'] = "Sort Code Asc";
$lang['sort_code_desc'] = "Sort Code Desc";
$lang['sort_date_asc'] = "Sort Date Asc";
$lang['sort_date_desc'] = "Sort Date Desc";
$lang['sort_title_asc'] = "Sort Title Asc";
$lang['sort_title_desc'] = "Sort Title Desc";
$lang['sort_views_asc'] = "Sort Views Asc";
$lang['sort_views_desc'] = "Sort Views Desc";
$lang['submit_error'] = "Submit Error";
$lang['user_exists'] = "User Exists";
$lang['views'] = "Views";
?>
