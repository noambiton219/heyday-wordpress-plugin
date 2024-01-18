function heydayShowLogin(msg) {
    document.write(`
    <div id='wp_login'>
        <img class="wp_login_logo" src="../wp-content/plugins/heyday-search/assets/LogoHeyDay.png" alt="HeyDay" />
        ${msg}
        <input placeholder="User name" type="text" name="" id="wp_login_name" value="${window.heyDaySettings.admin_email}" />
        <input placeholder="Password" type="password" name="" id="wp_login_pass" value="${window.heyday_randPassword}"/>
        <button id="hide_eye" onclick="togglePassVisibility()"><img src="../wp-content/plugins/heyday-search/assets/hide.png" /></button>
        <button id="wp_login_btn" onclick="wp_login()">Log in</button>
    </div>
    `);
  };

  async function autoSignup(){
  var blogname = window.blogname;
  var maxSizeInBytes = 30;
  var encoder = new TextEncoder();
  var originalBytes = encoder.encode(blogname);
  var substringBytes = originalBytes.slice(0, maxSizeInBytes);
  var substring;
  
  while (substringBytes.length > maxSizeInBytes) {
    substringBytes = substringBytes.slice(0, substringBytes.length - 1);
  }
  
  const decoder = new TextDecoder();
  substring = decoder.decode(substringBytes);
  
  const blob = new Blob([substring], { type: 'text/plain' });
  var blobText = await blob.text();

  sessionStorage.clear();

  var requestJson = {
    "action":1000,
    "uName":window.heyDaySettings.admin_email,
    "password":window.heyday_randPassword,
    "contactName": blobText,
    "click_src":"wordPress"
  };
  requestJson.successiveAction = {
      
      "action":17,
      "domainName":window.wpHost,
      "domainIcon":"",
      credentials:{
          "uName":window.heyDaySettings.admin_email,
          "password":window.heyday_randPassword
      },
      "configs":'[]'
  };
  const responseJson = await myFetch(requestJson, `https://heyday.io/panWbPush/OP`, {
      credentials: 'include',
      method: 'POST',
      headers: {
          'Content-Type': 'application/json',
      },
      body: JSON.stringify(requestJson),
  });
  if(typeof responseJson.affId != "undefined" && typeof responseJson.domains != "undefined")
  {
    var url = new URL(document.location.href);
    url.searchParams.append('heyDayAffId', responseJson.affId);
    url.searchParams.append('randPassword', window.heyday_randPassword);
    document.location.href = url;
  }
  else if(typeof responseJson.error != "undefined")
  {
    if(responseJson.error == 'username taken')
    {
      var url = new URL(document.location.href);
      url.searchParams.append('globalErr', '1');
      document.location.href = url;
    }
    else 
    {
      alert("installation error | "+ responseJson.error);
    }
  }
  else
  {
    alert("installation error | unresolved error");
  }
  }
  
  async function myFetch(requestJson, endpoint, data = {
    method : 'POST',
    headers: {
      'Content-Type': 'application/json'
    }
  }) {
    data.body = data.body || JSON.stringify(requestJson);
    let result;
    try {
      const f = await fetch(endpoint, data);
      result = await f.json();
    } catch(e){
      result = {error: e}
    }
    return result;
  };
  async function terminating(email,affId,password){
  sessionStorage.clear();
    let loginRequestJson = {
        'action': 1,
        'credentials': {
            'uName'   : email,
            'password': password,
        }
    }
    const loginResponse = await myFetch(loginRequestJson, `https://heyday.io/panWbPush/`, {
        credentials: 'include',
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(loginRequestJson),
    });
    const resp = loginResponse;
    const token = resp.accessToken;
  
    const requestJson = {
        'action': 30,
        'submitedAffId': affId,
    }
  
    await myFetch(requestJson, `https://admin.heyday.io/panWbPush/?c=1&accessToken=${token}&uName=${email}`, {
        credentials: 'include',
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        referrer: `https://admin.heyday.io/?u=${email}&t=${token}&a=wordPress`,
        referrerPolicy: "strict-origin-when-cross-origin",
        body: JSON.stringify(requestJson),
    });
  };
  
  async function wp_login() {
    sessionStorage.clear();
    let requestJson = {
        'action': 1,
        'credentials': {
            'uName'   : document.getElementById('wp_login_name').value,
            'password': document.getElementById('wp_login_pass').value,
        }
    }
    const loginResponse = await myFetch(requestJson, `https://heyday.io/panWbPush/`, {
        credentials: 'include',
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(requestJson),
    });
    const resp = loginResponse;
    const token = resp.accessToken;
  
    if(typeof window.heyDaySettings != "undefined" && typeof resp.accessToken !="undefined")
    {
    window.location.href = `https://admin.heyday.io/?u=${window.heyDaySettings.admin_email}${token ? '&t='+token : ''}&a=wordPress#/login`;    
    }
    else if(typeof resp.affId != "undefined" && typeof resp.accessToken !="undefined")
    {
      var url = new URL(document.location.href);
      url.searchParams.append('heyDayAffId', resp.affId);
      url.searchParams.append('accessToken', resp.accessToken);
    }else{
      alert("Incorrect E-Mail or password");
    }
  }
  
  function heyday_mannageAccount()
  {
  if(typeof window.heyDaySettings.affId != "undefined" && typeof window.heyday_queryParams.accessToken == "undefined")
  {
    var msg=`<p>Installation finished successfully. Your HeyDay account details are:</p>
    <p class="thin">Name: <span id="name_to_save">${window.heyDaySettings.admin_email}</span></p>
    <p class="thin">Password: <span id="pass_to_save">${window.heyday_randPassword_esc}</span></p>
    <p>Make sure to save it for the future</p>`;
    
    heydayShowLogin(msg);
  
    return;
  }
  else if(typeof window.heyday_queryParams.accessToken != "undefined")
  {
    window.location.href = `https://admin.heyday.io/?u=${window.heyDaySettings.admin_email}${window.heyday_queryParams.accessToken ? '&t='+window.heyday_queryParams.accessToken : ''}&a=wordPress#/login`; 
    return;
  }
  else if(typeof window.heyday_queryParams.globalErr != "undefined" && window.heyday_queryParams.globalErr == '1')
  {
    var msg=`<p>In order to finish the Installation.</p>
    <p>Login to your heyday account</p>
    Your HeyDay account details are:</p>
    <p class="thin">Name: <span id="name_to_save">${window.heyDaySettings.admin_email}</span></p>
    <p class="thin">Password: <span id="pass_to_save">${window.heyday_randPassword_esc}</span></p>`;
  
    heydayShowLogin(msg);
    return;
  }
  else
  {
    autoSignup();
  }
  
  }
  
  function heyday_reactivationSuccess(email,password)
  { 
  var msg=`<p>Login to your heyday account</p>
  Your HeyDay account details are:</p>
  <p class="thin">Name: <span id="name_to_save">${email}</span></p>
  <p class="thin">Password: <span id="pass_to_save">${password}</span></p>`;
    window.heyDaySettings = {"admin_email": email};
    window.heyday_randPassword = password;
    heydayShowLogin(msg);
  }
  
  function togglePassVisibility () {
    let x = document.getElementById("wp_login_pass");
    if (x.type === "password") {
        x.type = "text";
    } else {
        x.type = "password";
    }
  }
  
  jQuery(document).ready(function($) {
    var fetchUpdateTypesNonce = '';
    var selectPostTypeNonce = '';

  $.post(ajaxurl, { 'action': 'heyday_init_js' }, function(response) {
      fetchUpdateTypesNonce = response.fetchUpdateTypesNonce;
      selectPostTypeNonce = response.selectPostTypeNonce;
  });

  $.post(ajaxurl, { 'action': 'heyday_check_status' }, function(response) {
      if(response.status === 'completed') {
          var progress = Math.min(100, (response.processed_posts / response.total_posts) * 100);
          $('#progressBar').val(progress);
          $('#progressLabel').text(progress.toFixed(1) + '%, Pages loaded: ' + response.processed_posts + ', Your total pages:' + response.total_posts);
          if(response.web_type == 'product') {
            $('#hdy_demo').attr("href",`https://demo.heyday.io/searchDemo.html?sDomain=${response.domain}&nIfrm=1&box=ecommerceSideMenu&dir=rtl`);
          }else if(response.web_type != 'product') {
            $('#hdy_demo').attr("href",'https://demo.heyday.io/searchDemo.html?sDomain=' + response.domain);
          }
          $('#hdy-demo-container').show();
      }else if(response.status === 'in progress' && response.error !== 'error') {
        loadUserData();
      }else if(response.error === 'error'){
        console.log("Error with fetch products");
      }
  });
  
  var checkStatusInterval;
  var maxIndexPagesValue = 5000000;
  
  $('#stopLoad').click(function() {
    $.ajax({
    url: ajaxurl,
    type: 'POST',
    data: {
        'action': 'heyday_stop_load_progress', 
    },
    success: function(response) {
        $('#stopLoad').prop('disabled', true); 
        $('#loadUserDataBtn').prop('disabled', false); 
        if(checkStatusInterval)
          clearInterval(checkStatusInterval);
    }});
  })
  
  $('#loadUserDataBtn').click(function() {
    console.log("Loading user data...");
    $('#stopLoad').prop('disabled', false);
    $(this).prop('disabled', true);
    $('#progress-container').show();
    loadUserData();
   
});

function loadUserData() {
    var data = {
        'action': 'heyday_load_user_data',
    };
    $.post(ajaxurl, data, function(response) {
        if(response.status === 'in progress') {
          console.log(response); 
            startCheckStatusInterval();
            loadUserData();
        } else if(response.status === 'completed') {
          console.log(response); 
            startCheckStatusInterval();
            console.log("User data loaded successfully.");
        } else {
            console.log(response); 
        }
    }, "json");
}

$('#savePostTypes').click(function() {

  var selectedPostTypes = [];
  $('.postTypeCheckbox:checkbox:checked').each(function() {
      selectedPostTypes.push($(this).val());
  });
  $.ajax({
      url: ajaxurl,
      type: 'POST',
      data: {
          'action': 'heyday_select_posts_types',
          'loadType': 'update',
          'selectedPostTypes': selectedPostTypes,
          'heydayWpnonce': fetchUpdateTypesNonce,
      },
      success: function(response) {
        handleLoading();
        $('#loadUserDataBtn').show(); 
        $('#stopLoad').show();
        console.log(response);
      }
  });

});

function loadPostTypes() {
  $.ajax({
      url: ajaxurl,
      type: 'POST',
      data: {
          'action': 'heyday_select_posts_types',
          'loadType': 'fetch',
          'heydayWpnonce': fetchUpdateTypesNonce,
      },
      success: function(response) {
          $('#savePostTypes').show();
          $.each(response.data, function(key, value) {
              $('#postTypeContainer').append('<div style="margin-bottom: 5px;"><input type="checkbox" class="postTypeCheckbox" value="' + value + '">' + value + '</div>');
          });
      }
  });
}

  $('#newsSelected').click(function() {
    $(this).prop('disabled', true);
    $('#ecommerceSelected').hide();
    $('#loadMoreTypes').show();

    var maxIndexSelect = $('<select>', {
      id: 'maxIndex',
      name: 'maxIndex',
    });
  
    var options = ["Unlimited",1000, 2000 , 10000, 100000, 1000000, 2000000];
    for(var i = 0; i < options.length; i++) {
      var option = $('<option>', {
        value: options[i],
        text: options[i]
      });
      maxIndexSelect.append(option);
    }

    $('#newsSelected').after(maxIndexSelect);
    $('#maxIndex').before('<label>Select maximum index pages: </label>');

    $('#maxIndex').change(function() {
      maxIndexPagesValue = $(this).val();
      console.log(maxIndexPagesValue);
    });
    
  });
  
  var loadType = '';
  
  function handleLoading() {
    if(maxIndexPagesValue == 'Unlimited')
      maxIndexPagesValue = 5000000;
    $('#ecommerceSelected').hide();
    $('#newsSelected').hide();
    $.ajax({
      url: ajaxurl,
      type: 'POST',
      data: {
          'action': 'heyday_load_posts_and_products', 
          'loadType': loadType,
          'maxIndexPagesValue': parseInt(maxIndexPagesValue),
          'heydayWpnonce': selectPostTypeNonce,
      },
      success: function(response) {
        console.log(response);
      }
  });
  }
  $('#loadMoreTypes').click(function() {

    $(this).prop('disabled', true); 
    loadPostTypes();
   
  })
  $('#ecommerceSelected').click(function() {
    $(this).prop('disabled', true); 
    $('#newsSelected').hide();
    var maxIndexSelect = $('<select>', {
      id: 'maxIndex',
      name: 'maxIndex',
    });
  
    var options = ["Unlimited",1000, 2000 , 10000, 100000, 1000000, 2000000];
    for(var i = 0; i < options.length; i++) {
      var option = $('<option>', {
        value: options[i],
        text: options[i]
      });
      maxIndexSelect.append(option);
    }

    $('#ecommerceSelected').after(maxIndexSelect);
    $('#maxIndex').before('<label>Select maximum index pages: </label>');

    $('#maxIndex').change(function() {
      maxIndexPagesValue = $(this).val();
      console.log(maxIndexPagesValue);
    });
    
    $.ajax({
        url:ajaxurl,
        type: 'POST',
        data: {
            action: 'heyday_get_product_attributes_and_categories'
        },
        success: function(response) {
          var categories = response.categories;
          var attributes = response.attributes;
          var selected_categories = response.selected_categories;
          var selected_attributes = response.selected_attributes;
          var fields_priority = response.fields_priority;
  
          var container = $('#display-cat-att-container');
  
          container.append('<div id="categories" class="scrollable-container"><h3>Categories</h3></div>');
          for (var category in categories) {
              var isChecked = '';
              if(selected_categories.length === 0){
                  isChecked = 'checked';
              }else{
                  isChecked = (selected_categories.includes(categories)) ? 'checked' : '';
              }
              $('#categories').append('<input type="checkbox" id="' + category + '" name="' + category + '" '+isChecked+'><label for="' + category + '">' + categories[category] + '</label><br>');
          }
  
          container.append('<div id="attributes" class="scrollable-container"><h3>Attributes</h3></div>');
          for (var attribute in attributes) {
              var isChecked = '';
              if(selected_attributes.length === 0){
                  isChecked = 'checked';
              }else{
                  isChecked = (selected_attributes.includes(attribute)) ? 'checked' : '';
              }
              $('#attributes').append('<input type="checkbox" id="' + attribute + '" name="' + attribute + '" '+isChecked+'><label for="' + attribute + '">' + attributes[attribute] + '</label><br>');
          }
  
          var fields = [
            'title',
            'description',
            'short_description',
            'category',
            'sku',
            'brand_name',
            'custom_label_1', 
            'custom_label_2',
            'custom_label_3',
        ];
        var defaultPriorities = {
          'title': 2,
          'description': 1,
          'short_description':1,
          'category': 1,
          'sku':0,
      };
      var fieldsPriorityFromResponse = {};
      if (fields_priority) {
          fields_priority.forEach(function(item) {
              fieldsPriorityFromResponse[item.field] = item.priority;
          });
      }
        
        container.append('<div id="additionalFields" class="scrollable-container"><h3>Field Set Index</h3></div>');
        
        for (var i = 0; i < fields.length; i++) {
          var field = fields[i];
          var isChecked = fieldsPriorityFromResponse[field] !== undefined ? 'checked' : '';
          var priorityValue = fieldsPriorityFromResponse[field] || defaultPriorities[field] || 0;
          if(priorityValue > 0 || defaultPriorities[field] !== undefined){
            isChecked = 'checked';
          }
          
          $('#additionalFields').append(
              '<div style="margin-bottom: 5px;">' + '<input type="checkbox" id="' + field + '" name="' + field + '" ' + isChecked + '>' +
              '<label style="display: inline-block;width: 100px;" for="' + field + '">' + field + '</label>' +
              '<input type="number" id="' + field + '-priority" name="' + field + '-priority" min="0" max="3" value="' + priorityValue + '">' +
              '<label for="' + field + '-priority"> Priority</label><br>' + '</div>'
          );
        }

            container.append('<button id="hdy-submit" class="button button-primary">Submit</button>');

            $('#hdy-submit').click(function() {
                var selectedCategories = [];
                var selectedAttributes = [];
                var selectedFields = [];
                var fieldsPriority = [];
                
                $('#categories').find('input[type=checkbox]:checked').each(function() {
                    selectedCategories.push($(this).attr('name'));
                });
  
                $('#attributes').find('input[type=checkbox]:checked').each(function() {
                    selectedAttributes.push($(this).attr('name'));
                });
  
                $('#additionalFields').find('input[type=checkbox]:checked').each(function() {
                  selectedFields.push($(this).attr('name'));
                  var priority = $('#' + $(this).attr('name') + '-priority').val();
                  fieldsPriority.push({field: $(this).attr('name'), priority: priority});
              });

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'heyday_save_index_configuration',
                        selectedCategories: selectedCategories,
                        selectedAttributes: selectedAttributes,
                        selectedFields: selectedFields,
                        fieldsPriority: fieldsPriority,
                        heydayWpnonce: response.save_index_configuration_nonce,
                    },
                    success: function(response) {
                      $('#loadMoreTypes').show();
                    }
                });
            });
        }
    });
  });
  
  function startCheckStatusInterval() {
          $.post(ajaxurl, { 'action': 'heyday_check_status' }, function(response) {
              if(response.status === 'completed') {
                  console.log("Done");
                  $('#stopLoad').prop('disabled', true);
                  $('#loadUserDataBtn').prop('disabled', false);
                  var progress = Math.min(100, (response.processed_posts / response.total_posts) * 100);
                  $('#progressBar').val(progress);
                  $('#progressLabel').text(progress.toFixed(1) + '%, Total pages loaded: ' + response.processed_posts);
                  if(response.web_type == 'product' || response.web_type == 'postsAndProducts') {
                    $('#hdy_demo').attr("href",`https://demo.heyday.io/searchDemo.html?sDomain=${response.domain}&nIfrm=1&box=ecommerceSideMenu&dir=rtl`);
                  }else if(response.web_type != 'product') {
                    $('#hdy_demo').attr("href",'https://demo.heyday.io/searchDemo.html?sDomain=' + response.domain);
                  }
                  $('#hdy-demo-container').show();
              }else if(response.status === 'in progress' && response.error !== 'error') {
                console.log(response);
                var progress = Math.min(100, (response.processed_posts / response.total_posts) * 100);
                $('#progressBar').val(progress);
                $('#progressLabel').text(progress.toFixed(1) + '%, Indexed pages: ' + response.processed_posts);
              }else if(response.error === 'error'){
                console.log("Error with fetch products");
              }else{
                console.log(response.status);
              }
          });
  }
  });
  