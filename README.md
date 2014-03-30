Opauth-Live
================
[Opauth][1] strategy for  (Microsoft) Live Connect authentication.

Confusingly, Live Connect is (was) also known as:

- Windows Live
- Microsoft Passport
- Live ID
- MSN ID

Opauth is a multi-provider authentication framework for PHP.

Getting started
----------------
1. Install Opauth-Live:
   ```bash
   cd path_to_opauth/Strategy
   git clone git://github.com/uzyn/opauth-live.git Live
   ```

2. Create a Live Connect application at https://account.live.com/developers/applications
   - Once application is created, be sure to go to _My apps_ > _API Settings_ to define your _redirect domain_ to be `http://path_to_opauth/live/oauth2callback.`

3. Configure Opauth-Live strategy.

4. Direct user to `http://path_to_opauth/live` to authenticate

5. If `email` field is needed, add `wl.emails` to scope parameter in `LiveStrategy.php` (line 38). E.g., `'scope' => 'wl.basic wl.emails'`.

Strategy configuration
----------------------

Required parameters:

```php
<?php
'Live' => array(
  'client_id' => 'YOUR CLIENT ID',
  'client_secret' => 'YOUR CLIENT SECRET'
)
```
Optional parameters:
`scope`, `state`

References
------------
- [Live Connect developer guide](http://msdn.microsoft.com/en-us/library/live/hh243641)

License
---------
Opauth-GitHub is MIT Licensed  
Copyright © 2012 U-Zyn Chua (http://uzyn.com)

[1]: https://github.com/uzyn/opauth
