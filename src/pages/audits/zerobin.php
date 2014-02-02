<?php
    Upvote::render_arrows(
        "auditzerobin",
        "defuse_pages",
        "ZeroBin Security Audit",
        "A security audit of the ZeroBin pastebin.",
        "https://defuse.ca/audits/zerobin.htm"
    );
?>
<div class="pagedate">
February 2, 2014
</div>
<h1>ZeroBin Security Audit</h1>

<p>
This is the result of a 4-hour security audit of
<a href="http://sebsauvage.net/wiki/doku.php?id=php:zerobin">ZeroBin</a>. The
audit was performed for free as a service to the free/opensource community.
</p>

<pre>
-----------------------------------------------------------------------
&nbsp;&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; Security Audit of ZeroBin
&nbsp;&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; Taylor Hornby
&nbsp;&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; February 02, 2014
-----------------------------------------------------------------------

1. Introduction

&nbsp;&nbsp; ZeroBin&#039;s website [ZBW] describes ZeroBin as &quot;a minimalist,
&nbsp;&nbsp; opensource online pastebin/discussion board where the server has zero
&nbsp;&nbsp; knowledge of hosted data.&quot;

&nbsp;&nbsp; This report documents the results of a 4-hour security audit of
&nbsp;&nbsp; ZeroBin. It was performed for free as a service to the free and open
&nbsp;&nbsp; source software community.

&nbsp;&nbsp; The audit was performed on a current clone of ZeroBin&#039;s Git
&nbsp;&nbsp; repository [GR]. The commit hash is:

&nbsp;&nbsp; &nbsp; 4f8750bbddcb137213529875e45e3ace3be9a769

&nbsp;&nbsp; Note that this code is newer than the code available for download
&nbsp;&nbsp; from the ZeroBin website [ZBW] (version 0.18), which has known
&nbsp;&nbsp; vulnerabilities [XSS].

1.1 Audit Scope

&nbsp;&nbsp; This audit focused on the PHP side of ZeroBin. The JavaScript side
&nbsp;&nbsp; was only audited briefly, so most issues could not be verified, and
&nbsp;&nbsp; were added to Section 4, Future Work, below. The audit did NOT check
&nbsp;&nbsp; for XSS vulnerabilities in zerobin.js, and did NOT check for correct
&nbsp;&nbsp; usage of the SJCL cryptography library.

&nbsp;&nbsp; The audit did NOT cover the following files:

&nbsp;&nbsp; &nbsp; - lib/rain.tpl.class.php
&nbsp;&nbsp; &nbsp; - js/base64.js
&nbsp;&nbsp; &nbsp; - js/highlight.pack.js
&nbsp;&nbsp; &nbsp; - js/jquery.js
&nbsp;&nbsp; &nbsp; - js/rawdeflate.js
&nbsp;&nbsp; &nbsp; - js/rawinflate.js
&nbsp;&nbsp; &nbsp; - js/sjcl.js

2. Issues

2.1. Salt and HMAC Key Generated with mt_rand()

&nbsp;&nbsp; Exploitability: Medium
&nbsp;&nbsp; Security Impact: Low

&nbsp;&nbsp; In serversalt.php, generateRandomSalt() generates a salt using the
&nbsp;&nbsp; mt_rand() pseudo-random number generator. Because mt_rand() is not
&nbsp;&nbsp; a cryptographic random number generator, this function will return
&nbsp;&nbsp; easily-guessed salts with a much higher probability of collision.

&nbsp;&nbsp; The salts generated by this function are relied on for security. For
&nbsp;&nbsp; example, it is used as an HMAC key when checking delete tokens in
&nbsp;&nbsp; processPasteDelete(). It should be replaced with mcrypt_create_iv()
&nbsp;&nbsp; or openssl_random_pseudo_bytes().

&nbsp;&nbsp; Another unused mt_rand() salt generator appears in
&nbsp;&nbsp; vizhash_gd_zero.php. This should be removed.

&nbsp;&nbsp; This issue has already been reported in [GH68], but has not been
&nbsp;&nbsp; fixed (the issue is still open).

2.2. Fixed Server Salt

&nbsp;&nbsp; Exploitability: Low
&nbsp;&nbsp; Security Impact: Low

&nbsp;&nbsp; The security of the VizHash hashes of IP addresses (used to generate
&nbsp;&nbsp; comment avatars) and delete tokens both rely on a fixed &quot;salt&quot; which
&nbsp;&nbsp; is generated once per deployment, saved in data/salt.php.

&nbsp;&nbsp; As noted in Issue 2.1, this salt is not generated with a secure
&nbsp;&nbsp; random number, but keeping it fixed has several other disadvantages:

&nbsp;&nbsp; &nbsp; - If the salt is compromised and published, anyone can reverse
&nbsp;&nbsp; &nbsp; &nbsp; a VizHash into the corresponding IP address, even for expired
&nbsp;&nbsp; &nbsp; &nbsp; posts (that they saved). This would not be possible if a random
&nbsp;&nbsp; &nbsp; &nbsp; comment salt was generated for each post, then destroyed along
&nbsp;&nbsp; &nbsp; &nbsp; with the post when it expires. This would, however, give users
&nbsp;&nbsp; &nbsp; &nbsp; different avatars on different posts (which may be desired).

&nbsp;&nbsp; &nbsp; - If the salt is compromised and published, anyone can find the
&nbsp;&nbsp; &nbsp; &nbsp; delete link for any post, since they can compute the HMAC. This
&nbsp;&nbsp; &nbsp; &nbsp; does not need to be possible. A random delete token could be
&nbsp;&nbsp; &nbsp; &nbsp; generated for each post, and its hash stored along with the post.
&nbsp;&nbsp; &nbsp; &nbsp; Then, the post could only be deleted if the user can provide the
&nbsp;&nbsp; &nbsp; &nbsp; preimage of the hash.

&nbsp;&nbsp; &nbsp; - Reusing the same key for two purposes is generally a bad idea.

2.3. Traffic Limiter Race Conditions

&nbsp;&nbsp; To rate limit requests, ZeroBin keeps a history of hit times in
&nbsp;&nbsp; data/traffic_limiter.php, which is generated and re-generated in
&nbsp;&nbsp; traffic_limiter_canPass(). Because requests can be made
&nbsp;&nbsp; asynchronously, the file may become corrupted.

&nbsp;&nbsp; This might allow remote code execution, if the attacker is clever
&nbsp;&nbsp; enough to corrupt the file in just the right way, since the
&nbsp;&nbsp; traffic_limiter.php file is require()&#039;d.

&nbsp;&nbsp; This issue has already been reported, and a patch has been provided,
&nbsp;&nbsp; in [GH61], but has not been fixed (the issue is still open).

2.4. VizHash IP Address Online Guessing

&nbsp;&nbsp; Exploitability: Very Low
&nbsp;&nbsp; Security Impact: Low

&nbsp;&nbsp; The VizHash system is used to give users who comment an avatar
&nbsp;&nbsp; derived from their IP address. If an attacker can create connections
&nbsp;&nbsp; to the ZeroBin server from arbitrary source IPs (e.g. if they are in
&nbsp;&nbsp; the same LAN as the ZeroBin server, or man-in-the-middling its
&nbsp;&nbsp; traffic), they can perform an online guessing attack on the VizHash
&nbsp;&nbsp; they are interested in.

2.5. Relies on .htaccess files, which may not be enabled.

&nbsp;&nbsp; Exploitability: N/A
&nbsp;&nbsp; Security Impact: Very Low

&nbsp;&nbsp; ZeroBin relies on .htaccess files being enabled to prevent access to
&nbsp;&nbsp; the &#039;data&#039; directory. This directory contains the ciphertexts, salt,
&nbsp;&nbsp; and rate limiter array. If .htaccess is disabled, of if ZeroBin is
&nbsp;&nbsp; installed on a non-Apache web server, then an attacker may be able to
&nbsp;&nbsp; access these files.&nbsp;
&nbsp;&nbsp;&nbsp;
&nbsp;&nbsp; The salt and rate limiter array are safe since they are in .php
&nbsp;&nbsp; files. However, the attacker would be able to access the ciphertext.
&nbsp;&nbsp; This is not a security issue because the attacker already has access
&nbsp;&nbsp; to the ciphertext.

&nbsp;&nbsp; If directory traversal is enabled, the attacker can see all of the
&nbsp;&nbsp; post ids, too.

2.6. The robots.txt does not work in a subdomain.

&nbsp;&nbsp; Exploitability: N/A
&nbsp;&nbsp; Security Impact: Low

&nbsp;&nbsp; ZeroBin uses a robots.txt file to prevent search engines from
&nbsp;&nbsp; indexing the posts. If you install ZeroBin into a subdirectory, this
&nbsp;&nbsp; does not work. A note about this should be added to the README or
&nbsp;&nbsp; other documentation, so that the user knows to install the robots.txt
&nbsp;&nbsp; file in the right place.

2.7 HMAC Not Compared in Constant Time

&nbsp;&nbsp; Exploitability: Medium
&nbsp;&nbsp; Security Impact: Low

&nbsp;&nbsp; ZeroBin uses an HMAC to generate and check delete tokens. The HMAC is
&nbsp;&nbsp; compared against the correct one with PHP&#039;s &quot;!=&quot; operator. A timing
&nbsp;&nbsp; attack can exploit this error to compute the HMAC of arbitrary data
&nbsp;&nbsp; with the server&#039;s salt.

&nbsp;&nbsp; ZeroBin should check if the strings are equal in constant time:

&nbsp;&nbsp; &nbsp; function slow_equals($a, $b)
&nbsp;&nbsp; &nbsp; {
&nbsp;&nbsp; &nbsp; &nbsp; &nbsp; $diff = strlen($a) ^ strlen($b);
&nbsp;&nbsp; &nbsp; &nbsp; &nbsp; for($i = 0; $i &lt; strlen($a) &amp;&amp; $i &lt; strlen($b); $i++)
&nbsp;&nbsp; &nbsp; &nbsp; &nbsp; {
&nbsp;&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; $diff |= ord($a[$i]) ^ ord($b[$i]);
&nbsp;&nbsp; &nbsp; &nbsp; &nbsp; }
&nbsp;&nbsp; &nbsp; &nbsp; &nbsp; return $diff === 0;
&nbsp;&nbsp; &nbsp; }

2.8. Arbitrary File Unlink

&nbsp;&nbsp; Exploitability: Medium
&nbsp;&nbsp; Security Impact: High

&nbsp;&nbsp; An attacker can delete arbitrary files on the server by exploiting
&nbsp;&nbsp; a vulnerability in processPasteDelete(). Here is a condensed version
&nbsp;&nbsp; of the code:

&nbsp;&nbsp; function processPasteDelete($pasteid,$deletetoken)
&nbsp;&nbsp; {
&nbsp;&nbsp; &nbsp; &nbsp; if (preg_match(&#039;/\A[a-f\d]{16}\z/&#039;,$pasteid)) &nbsp;
&nbsp;&nbsp; &nbsp; &nbsp; {
&nbsp;&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; $filename = dataid2path($pasteid).$pasteid;
&nbsp;&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; if (!is_file($filename))&nbsp;
&nbsp;&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; {
&nbsp;&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; return ... error message ...
&nbsp;&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; }
&nbsp;&nbsp; &nbsp; &nbsp; }
&nbsp;&nbsp;&nbsp;
&nbsp;&nbsp; &nbsp; &nbsp; if ($deletetoken != hash_hmac(&#039;sha1&#039;, $pasteid , getServerSalt()))&nbsp;
&nbsp;&nbsp; &nbsp; &nbsp; {
&nbsp;&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; return ... error message ...
&nbsp;&nbsp; &nbsp; &nbsp; }
&nbsp;&nbsp;&nbsp;
&nbsp;&nbsp; &nbsp; &nbsp; deletePaste($pasteid);
&nbsp;&nbsp; &nbsp; &nbsp; return array(&#039;&#039;,&#039;&#039;,&#039;Paste was properly deleted.&#039;);
&nbsp;&nbsp; }

&nbsp;&nbsp; The $pasteid variable comes directly from $_GET[&#039;pasteid&#039;], which the
&nbsp;&nbsp; attacker can control. The $deletetoken variable comes from
&nbsp;&nbsp; $_GET[&#039;deletetoken&#039;]. If $deletetoken matches the HMAC, $pasteid is
&nbsp;&nbsp; passed to deletePaste(), which runs:
&nbsp;&nbsp;&nbsp;
&nbsp;&nbsp; &nbsp; unlink(dataid2path($pasteid).$pasteid);

&nbsp;&nbsp; &nbsp;Thus, if an attacker can compute the HMAC, they can delete arbitrary
&nbsp;&nbsp; &nbsp;files on the server. The HMAC can be computed if the salt is known.
&nbsp;&nbsp; &nbsp;Forging the HMAC without already knowing the salt is easy because of
&nbsp;&nbsp; &nbsp;Issue 2.7 and Issue 2.1.

2.9. HMAC Uses SHA1 instead of SHA256

&nbsp;&nbsp; Exploitability: Very Low
&nbsp;&nbsp; Security Impact: Low

&nbsp;&nbsp; ZeroBin uses a SHA1 HMAC to derive the delete token. SHA1 should be
&nbsp;&nbsp; replaced with a better hash function, like SHA256.

2.10. No Cross-Site Request Forgery Protection

&nbsp;&nbsp; Exploitability: Medium
&nbsp;&nbsp; Security Impact: Medium

&nbsp;&nbsp; ZeroBin does not attempt to prevent Cross-Site Request Forgery (CSRF)
&nbsp;&nbsp; attacks. A malicious website can make a user&#039;s browser delete ZeroBin
&nbsp;&nbsp; posts (if the delete token is known), create posts, and post comments
&nbsp;&nbsp; to existing posts.

&nbsp;&nbsp; A CSRF attack to post comments can be a problem when a malicious
&nbsp;&nbsp; website wants to find out the ZeroBin VizHash of its visitors, to see
&nbsp;&nbsp; what they have been commenting. The attack would work as follows:

&nbsp;&nbsp; &nbsp; 1. Alice suspects Bob posted a rude ZeroBin comment.
&nbsp;&nbsp; &nbsp; 2. Alice generates a web page that causes Bob&#039;s browser to comment
&nbsp;&nbsp; &nbsp; &nbsp; &nbsp;&quot;I AM BOB!&quot; on a ZeroBin post.
&nbsp;&nbsp; &nbsp; 3. Alice emails a link to the malicious page to Bob.
&nbsp;&nbsp; &nbsp; 4. Bob clicks the link.
&nbsp;&nbsp; &nbsp; 5. Alice checks the post, sees that the &quot;I AM BOB!&quot; comment has the
&nbsp;&nbsp; &nbsp; &nbsp; &nbsp;same VizHash as the rude comment. Alice now knows it was Bob
&nbsp;&nbsp; &nbsp; &nbsp; &nbsp;that made the rude comment.

3. Coding Practices

&nbsp;&nbsp; The ZeroBin code could be made more secure, and easier to audit, by
&nbsp;&nbsp; following some good coding practices. These are documented in the
&nbsp;&nbsp; next sections.

3.1. Always Assume Malice

&nbsp;&nbsp; When a string is used in a way that might affect security, it should
&nbsp;&nbsp; always be assumed to be malicious, even if it is just a constant
&nbsp;&nbsp; string. The ZeroBin code does not do this, for example:

&nbsp;&nbsp; &nbsp; - In the post creation code, it assumes $dataid is safe, which it
&nbsp;&nbsp; &nbsp; &nbsp; is, since it it is a hex string from an MD5 hash, but it would be
&nbsp;&nbsp; &nbsp; &nbsp; better if the code assumed it was malicious and checked/escaped
&nbsp;&nbsp; &nbsp; &nbsp; it anyway.

&nbsp;&nbsp; &nbsp; - Several strings are not escaped in tpl/page.html. $VERSION is not
&nbsp;&nbsp; &nbsp; &nbsp; escaped in the header, and $STATUS is not escaped in the body.
&nbsp;&nbsp; &nbsp; &nbsp; Even though these are static strings, they should be escaped
&nbsp;&nbsp; &nbsp; &nbsp; anyway, since someone might change the error messages to reflect
&nbsp;&nbsp; &nbsp; &nbsp; user input in the future. See Section 4.6.

4. Future Work

4.1. Secure Code Delivery

&nbsp;&nbsp; ZeroBin relies on the JavaScript code being delivered securely.
&nbsp;&nbsp; A malicious server or man-in-the-middle can modify the code to leak
&nbsp;&nbsp; the key. This is already well known and is being addressed in [GH17].

4.2. urls2links XSS

&nbsp;&nbsp; In zerobin.js, urls2links() converts a URL text into a clickable
&nbsp;&nbsp; link. The replacement is done purely by regular expression, and no
&nbsp;&nbsp; escaping is done. This may be a source of XSS vulnerabilities. I did
&nbsp;&nbsp; not have enough time to understand what the regular expression is
&nbsp;&nbsp; doing, so I&#039;m not sure if this is exploitable or not.

&nbsp;&nbsp; There are other bits of code that look like they might be XSS
&nbsp;&nbsp; vulnerabilities (e.g. line 233 in zerobin.js where divComment is
&nbsp;&nbsp; set). I did not have enough time to check these in detail.

4.3. Low Entropy in Browsers Without CSPRNGs

&nbsp;&nbsp; If the web browser does not have window.crypto.getRandomValues(), the
&nbsp;&nbsp; key is derived from mouse movements and stuff. That may not be good
&nbsp;&nbsp; enough. Perhaps in these cases the server could help the client by
&nbsp;&nbsp; supplying some of its own entropy from mcrypt_create_iv() or
&nbsp;&nbsp; openssl_random_pseudo_bytes().

4.4. Plaintext is Compressed Before Encryption

&nbsp;&nbsp; Posts are compressed before they are encrypted. This makes the
&nbsp;&nbsp; ciphertext length depend on the plaintext content. This may create
&nbsp;&nbsp; vulnerabilities in specific use cases where an attacker can choose
&nbsp;&nbsp; variations of the same post to be encrypted. &nbsp;This would be similar
&nbsp;&nbsp; to the CRIME and BREACH attacks, except happening at the application
&nbsp;&nbsp; layer.

4.5. Is SJCL used correctly?

&nbsp;&nbsp; This audit did not check if zerobin.js uses the SJCL cryptography
&nbsp;&nbsp; library correctly.

4.6. Possible XSS in tpl/page.html

&nbsp;&nbsp; JSON encoded data is inserted into the HTML page unescaped in
&nbsp;&nbsp; tpl/page.html. This is because $STATUS is set to the return value of
&nbsp;&nbsp; processPasteFetch(), which returns JSON encoded data based on the
&nbsp;&nbsp; user&#039;s input. I did not check if this is exploitable.

5. Conclusion

&nbsp;&nbsp; Several issues were found. The most critical being the arbitrary file
&nbsp;&nbsp; unlink vulnerability, described in Section 2.8, and the use of
&nbsp;&nbsp; mt_rand() to generate HMAC keys, described in Section 2.1.

&nbsp;&nbsp; This audit did not cover the JavaScript cryptography, nor did it
&nbsp;&nbsp; cover XSS vulnerabilities in the JavaScript code. More auditing time
&nbsp;&nbsp; is recommended.

6. References

&nbsp;&nbsp; [GH17] https://github.com/sebsauvage/ZeroBin/issues/17
&nbsp;&nbsp; [GH68] https://github.com/sebsauvage/ZeroBin/issues/68
&nbsp;&nbsp; [GH61] https://github.com/sebsauvage/ZeroBin/pull/61
&nbsp;&nbsp; [GR] &nbsp; https://github.com/sebsauvage/ZeroBin
&nbsp;&nbsp; [ZBW] &nbsp;http://sebsauvage.net/wiki/doku.php?id=php:zerobin
&nbsp;&nbsp; [XSS] &nbsp;https://github.com/sebsauvage/ZeroBin/commit/4f8750bbddcb137213529875e45e3ace3be9a769
</pre>
