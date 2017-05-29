//via https://jwt.io/js/app.js

(function () {
  // Taken from http://stackoverflow.com/questions/2490825/how-to-trigger-event-in-javascript
  function fireEvent(element) {
    var event; // The custom event that will be created

    if (document.createEvent) {
      event = document.createEvent('HTMLEvents');
      event.initEvent('change', true, true);
    } else {
      event = document.createEventObject();
      event.eventType = 'change';
    }

    event.eventName = 'change';

    if (document.createEvent) {
      element.dispatchEvent(event);
    } else {
      element.fireEvent('on' + event.eventType, event);
    }
  }

  var DEFAULT_HS_TOKEN = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9lIiwiYWRtaW4iOnRydWV9.TJVA95OrM7E2cBab30RMHrHDcEfxjoYZgeFONFh7HgQ';

  var DEFAULT_RS_TOKEN = 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9lIiwiYWRtaW4iOnRydWV9.EkN-DOsnsuRjRO6BxXemmJDm3HbxrbRzXglbN2S4sOkopdU4IsDxTI8jO19W_A4K8ZPJijNLis4EZsHeY559a4DFOd50_OqgHGuERTqYZyuhtF39yxJPAjUESwxk2J5k_4zM3O-vtd1Ghyo4IbqKKSy6J9mTniYJPenn5-HIirE';

  var DEFAULT_PUBLIC_RSA = "\
-----BEGIN PUBLIC KEY-----\n\
MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDdlatRjRjogo3WojgGHFHYLugd\
UWAY9iR3fy4arWNA1KoS8kVw33cJibXr8bvwUAUparCwlvdbH6dvEOfou0/gCFQs\
HUfQrSDv+MuSUMAe8jzKE4qW+jK+xQU9a03GUnKHkkle+Q0pX/g6jXZ7r1/xAK5D\
o2kQ+X5xK9cipRgEKwIDAQAB\n\
-----END PUBLIC KEY-----\
  ";

  var DEFAULT_PRIVATE_RSA = "\
-----BEGIN RSA PRIVATE KEY-----\n\
MIICWwIBAAKBgQDdlatRjRjogo3WojgGHFHYLugdUWAY9iR3fy4arWNA1KoS8kVw\
33cJibXr8bvwUAUparCwlvdbH6dvEOfou0/gCFQsHUfQrSDv+MuSUMAe8jzKE4qW\
+jK+xQU9a03GUnKHkkle+Q0pX/g6jXZ7r1/xAK5Do2kQ+X5xK9cipRgEKwIDAQAB\
AoGAD+onAtVye4ic7VR7V50DF9bOnwRwNXrARcDhq9LWNRrRGElESYYTQ6EbatXS\
3MCyjjX2eMhu/aF5YhXBwkppwxg+EOmXeh+MzL7Zh284OuPbkglAaGhV9bb6/5Cp\
uGb1esyPbYW+Ty2PC0GSZfIXkXs76jXAu9TOBvD0ybc2YlkCQQDywg2R/7t3Q2OE\
2+yo382CLJdrlSLVROWKwb4tb2PjhY4XAwV8d1vy0RenxTB+K5Mu57uVSTHtrMK0\
GAtFr833AkEA6avx20OHo61Yela/4k5kQDtjEf1N0LfI+BcWZtxsS3jDM3i1Hp0K\
Su5rsCPb8acJo5RO26gGVrfAsDcIXKC+bQJAZZ2XIpsitLyPpuiMOvBbzPavd4gY\
6Z8KWrfYzJoI/Q9FuBo6rKwl4BFoToD7WIUS+hpkagwWiz+6zLoX1dbOZwJACmH5\
fSSjAkLRi54PKJ8TFUeOP15h9sQzydI8zJU+upvDEKZsZc/UhT/SySDOxQ4G/523\
Y0sz/OZtSWcol/UMgQJALesy++GdvoIDLfJX5GBQpuFgFenRiRDabxrE9MNUZ2aP\
FaFp+DyAe+b4nDwuJaW2LURbr8AEZga7oQj0uYxcYw==\n\
  -----END RSA PRIVATE KEY-----\
  ";

  var codeMirror = CodeMirror;

  function tabHack(instance) {
    instance.replaceSelection('   ' , 'end');
  }

  var tokenEditor = codeMirror(document.getElementsByClassName('js-input')[0], {
    mode:           'jwt',
    theme:          'night',
    lineWrapping:   true,
    // autofocus:      true,
    extraKeys: { 'Tab':  tabHack}
  });

  var headerEditor = codeMirror(document.getElementsByClassName('js-header')[0], {
    mode:           'application/json',
    lineWrapping:   true,
    extraKeys: { 'Tab':  tabHack},
    lint: true
  });

  var payloadEditor = codeMirror(document.getElementsByClassName('js-payload')[0], {
    mode:           'application/json',
    lineWrapping:   true,
    extraKeys: { 'Tab':  tabHack},
    lint: true
  });

  var algorithmRadios = $('input[name="algorithm"]'),
      lastRestoredToken;
  var tokenRadios = $('input[name="token-type"]');

  function setJSONEditorContent(jsonEditor, decodedJSON, selector) {
    jsonEditor.off('change', refreshTokenEditor);

    if (decodedJSON.result !== null && decodedJSON.result !== undefined) {
      jsonEditor.setValue(decodedJSON.result);
    } else {
      jsonEditor.setValue('');
    }
    if (decodedJSON.error) {
      selector.addClass('error');
    } else {
      selector.removeClass('error');
    }

    jsonEditor.on('change', refreshTokenEditor);

  }


  function tokenEditorOnChangeListener(instance) {
    var value = getTrimmedValue(instance);

    if (!value) { return; }

    var parts = value.split('.');

    var secretElement = document.getElementsByName('secret')[0];
    var signatureElement = getFirstElementByClassName('js-signature');

    if (!signatureElement) {
      return;
    }

    var decodedHeader = window.decode(parts[0]);

    try {
      selectDetectedAlgorithm(JSON.parse(decodedHeader.result).alg);
    }catch (e){
      console.error('Invalid header decoded');
    }

    var selector = $('.jwt-header');
    setJSONEditorContent(headerEditor, decodedHeader, selector);
    var decodedPayload = window.decode(parts[1]);
    selector = $('.jwt-payload');
    setJSONEditorContent(payloadEditor, decodedPayload, selector);

    fireEvent(secretElement);

    if (window.matchMedia('(min-width: 768px)').matches) {
      autoHeightInput();
    }
  }

  function selectDetectedAlgorithm(alg){
    var $algRadio = $('.algorithm input[value="'+alg+'"]');
    $algRadio.prop('checked', true);

    fireEvent($algRadio.get(0));
  }

  function saveToStorage(jwt) {
    // Save last valid jwt value for refresh
    safeLocalStorageSetItem("jwtValue", jwt);
  }

  function loadFromStorage(cb) {
    cb(localStorage.getItem("jwtValue"));
    localStorage.removeItem("jwtValue");
  }

  function refreshTokenEditor(instance) {
    tokenEditor.off('change', tokenEditorOnChangeListener);

    var algorithm = getAlgorithm();
    var secretElement = document.getElementsByName('secret')[0];
    var isBase64EncodedElement = document.getElementsByName('is-base64-encoded')[0];

    var signResult = window.sign(
      algorithm,
      headerEditor.getValue(),
      payloadEditor.getValue(),
      getKey(algorithm, 'sign'),
      isBase64EncodedElement.checked
    );

    if (signResult.error) {
      tokenEditor.setValue('');
      var elements = {'payload': '.jwt-payload', 'header': '.jwt-header'};
      $('.jwt-payload').removeClass('error');
      $('.jwt-header').removeClass('error');
      if (signResult.error.who) {
        signResult.error.who
          .map(function (e) { return elements[e]; })
          .forEach(function (e) {
            $(e).addClass('error');
          });
      }
      $('.input').addClass('error');
      if (signResult.result) {
        tokenEditor.setValue(signResult.result);
      } else {
        tokenEditor.setValue('');
      }
    } else {
      tokenEditor.setValue(signResult.result);
      $('.input').removeClass('error');
      $('.jwt-payload').removeClass('error');
      $('.jwt-header').removeClass('error');

      saveToStorage(signResult.result);
    }
    tokenEditor.on('change', tokenEditorOnChangeListener);
    fireEvent(secretElement);
  }

  function getFirstElementByClassName(selector) {
    var headerElement = document.getElementsByClassName(selector);
    return headerElement.length ? headerElement[0] : null;
  }

  function getTrimmedValue(instance) {
    var value = instance.getValue();
    if (!value) {
      return null;
    }

    return value.replace(/\s/g, '');
  }

  tokenEditor.on('change', tokenEditorOnChangeListener);

  payloadEditor.on('change',  refreshTokenEditor);
  headerEditor.on('change',   refreshTokenEditor);

  var secretElement = document.getElementsByName('secret')[0];
  var isBase64EncodedElement = document.getElementsByName('is-base64-encoded')[0];

  function updateSignature () {
    var algorithm = getAlgorithm();
    var signatureElement = getFirstElementByClassName('js-signature');
    var signatureContainerElement = getFirstElementByClassName('jwt-signature');

    if (!signatureElement) {
      return;
    }
    var value = getTrimmedValue(tokenEditor);
    var isBase64 = isBase64EncodedElement.checked;
    if (isBase64 && !window.isValidBase64String(secretElement.value)) {
      $(signatureContainerElement).addClass('error');
      return;
    } else {
      $(signatureContainerElement).removeClass('error');
    }

    var result = window.verify(
      algorithm,
      value,
      getKey(algorithm, 'verify'),
      isBase64
    );

    var error = result.error;
    result = result.result;
    if (!error && result) {
      $(signatureElement).removeClass('invalid-token');
      $(signatureElement).addClass('valid-token');
      signatureElement.innerHTML = '<i class="icon-budicon-499"></i> signature verified';
    } else {
      $(signatureElement).removeClass('valid-token');
      $(signatureElement).addClass('invalid-token');
      signatureElement.innerHTML = '<i class="icon-budicon-501"></i> invalid signature';
    }
  }

  function getKey(algorithm, action) {
    var secretElement = $('input[name="secret"]');
    var privateKeyElement = $('textarea[name="private-key"]');
    var publicKeyElement = $('textarea[name="public-key"]');

    if(algorithm === 'HS256') {
        return secretElement.val();
    } else {
        return action === 'sign' ? privateKeyElement.val() : publicKeyElement.val();
    }
  }

  function getAlgorithm() {
    return algorithmRadios.filter(':checked').val();
  }

  function updateAlgorithm () {
    var algorithm = algorithmRadios.filter(':checked').val();

    $('.js-input').attr('data-alg', algorithm);

    $('.jwt-signature pre')
        .hide()
        .filter('.' + algorithm)
        .show();

    if(getTokenType() === 'id_token' && getTrimmedValue(tokenEditor) === DEFAULT_HS_TOKEN &&
      algorithm === 'RS256'){
        setDefaultsForRSA();
    }else if(getTokenType() === 'id_token' && getTrimmedValue(tokenEditor) === DEFAULT_RS_TOKEN &&
      algorithm === 'HS256'){
        setDefaultsForHMAC();
    }
  }

  function setDefaultsForRSA() {
    tokenEditor.setValue(DEFAULT_RS_TOKEN);

    $('.jwt-signature textarea[name=public-key]').val(DEFAULT_PUBLIC_RSA);
    $('.jwt-signature textarea[name=private-key]').val(DEFAULT_PRIVATE_RSA);
  }

  function setDefaultsForHMAC(){
    tokenEditor.setValue(DEFAULT_HS_TOKEN);
  }

  function updateToken() {
    var tokenType = getTokenType();
    if (document.location.hash) {
      var qs = document.location.hash.slice(1);
      var d = {};
      qs = qs.split('&');
      qs.forEach(function (kv) { kv = kv.split('='); d[kv[0]] = kv[1]; });

      if (d[tokenType]) {
        tokenEditor.setValue(decodeURIComponent(d[tokenType]));
        return;
      }
    }
  }

  function getTokenType() {
    return tokenRadios.filter(':checked').val();
  }

  function validateKey() {
    var $textarea = $(this);
    var valid;

    if($textarea.prop('name') === 'public-key') {
      valid = /-----BEGIN (PUBLIC KEY|CERTIFICATE)-----(.|\n)*-----END (PUBLIC KEY|CERTIFICATE)-----/.test($textarea.val());
    } else {
      valid = /-----BEGIN RSA PRIVATE KEY-----(.|\n)*-----END RSA PRIVATE KEY-----/.test($textarea.val());
    }

    if (valid) {
      $textarea.removeClass('error');
    } else {
      $textarea.addClass('error');
    }
  }

  updateAlgorithm();


  algorithmRadios.on('change', function(){
    updateAlgorithm();
    updateSignature();
  });

  tokenRadios.on('change', function(){
    updateToken();
    updateAlgorithm();
    updateSignature();
  });

  $('.jwt-signature textarea[name="public-key"]').on('input', updateSignature);
  $('.jwt-signature textarea[name="private-key"]').on('input', function () {
    validateKey.apply(this);
    refreshTokenEditor();
  });


  secretElement.addEventListener('change', updateSignature, false);
  secretElement.addEventListener('keyup', updateSignature, false);

  isBase64EncodedElement.addEventListener('change', updateSignature, false);

  if (document.location.search) {
    var qs = document.location.search.slice(1);
    var d = {};
    qs = qs.split('&');
    qs.forEach(function (kv) { kv = kv.split('='); d[kv[0]] = kv[1]; });
    if (d.value) {
      tokenEditor.setValue(decodeURIComponent(d.value));
      return;
    }
  }

  if (document.location.hash) {
    var qs = document.location.hash.slice(1);
    var d = {};
    qs = qs.split('&');
    qs.forEach(function (kv) { kv = kv.split('='); d[kv[0]] = kv[1]; });

    if (d.access_token && d.id_token) {
      // show token-type selector
      $('.jwt-playground .selections .token-type').show();
    }

    if (d.id_token) {
      tokenEditor.setValue(decodeURIComponent(d.id_token));
      return;
    }

    if (d.access_token) {
      tokenEditor.setValue(decodeURIComponent(d.access_token));
      return;
    }
  }

  loadFromStorage(function (jwt) {
    lastRestoredToken = jwt || DEFAULT_HS_TOKEN;

    tokenEditor.setValue(
      lastRestoredToken
    );
  });

}());

var t = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJmb28iOiJiYXIifQ.EftYoAbOOcSVt0YkogEufgA_wrekVyFpTJ-wPExyj3A';
window.verify('HS256', t, 'aHR0cGxvY2Fsd29yZHByZXNzZGV2ZDI4MTM4OWFmNzU4MGYyZDdmZTc1MGUxMDAxYzBmM2RlY2U1OTE0YmZkZWJjMWE5OGJiMDc4MjUwN2Y5NTk5MHBm', false);
var parts = t.split('.');
window.decode(parts[1]);

window.vartesta = window.sign('HS256', '{ "typ": "JWT", "alg": "HS256" }', '{ "foo": "bar" }', 'aHR0cGxvY2Fsd29yZHByZXNzZGV2ZDI4MTM4OWFmNzU4MGYyZDdmZTc1MGUxMDAxYzBmM2RlY2U1OTE0YmZkZWJjMWE5OGJiMDc4MjUwN2Y5NTk5MHBm', false );
2<pre><?php
var_dump($pub = pressforward('controller.jwt')->get_a_user_public_key());
var_dump($priv = pressforward('controller.jwt')->get_a_user_private_key_for_decrypt($pub));
var_dump(pressforward('controller.jwt')->decode_with_jwt("eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJmb28iOiJiYXIifQ.EftYoAbOOcSVt0YkogEufgA_wrekVyFpTJ-wPExyj3A", $priv));
