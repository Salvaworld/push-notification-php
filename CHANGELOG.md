#   ChangeLog

##  v2.0.6
Fix APN token authentication: the ES256 signature of the provider token was sent in ASN.1 DER format instead of the raw
64 byte format, so Apple answered every push with `InvalidProviderToken`. The token is now signed with firebase/php-jwt.

APN gateway fallback: a device token only works on the gateway of the build that registered it (sandbox for Xcode
builds, production for TestFlight and App Store builds). When Apple answers `BadDeviceToken`, the push is now retried
once on the other gateway. Each result carries a `gateway` key saying which one answered. Set `gateway_fallback` to
false in the apn config to turn this off.

##  v2.1
Laravel Facade Usage integrated into the package.

Now if you wanna get the Push Feedback after sending a notification you can chain the method getFeedback after send method.


##  v2.0

Now you can create the object with the push service provider only typing the name

    $push = new PushNotification('apn');

Addition of a new Push service provider : FCM

    $push = new PushNotification('fcm');

As previous versions, if you instance the class without argument. it will set GCM as default Push service provider

    $push = new PushNotification();


##  v1.0

Usage

    $push = new PushNotification;

or for APN

    $push = new PushNotification(new \SalvaWorld\PushNotification\Apn());
