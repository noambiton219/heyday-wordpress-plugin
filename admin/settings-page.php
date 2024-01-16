<div class="wrap">
    <div class="select-wrapper">
        <h2 class="center-item">Welcome To HeyDay Search Configuration</h2>
        <h4 class="center-item">Select your website type</h4>
        <div class="web-type-container">
            <button id="newsSelected" class="button button-primary">Media / News</button>
            <button id="ecommerceSelected" class="button button-primary">Ecommerce</button>
        </div>
        <div id="display-cat-att-container"></div>
    </div>
    <div>
        <button id="loadMoreTypes" class="button button-secondary" style="margin: 10px 0 10px 0;display: none;">Select Types To Index</button>
        <div id="postTypeContainer"></div>
        <button id="savePostTypes" class="button button-primary" style="display: none;">Save Post Type</button>
    </div>
    <button style="display: none;margin-top:10px;" id="loadPostsOnlyBtn" class="button button-primary">Index Post Only</button>
    <button style="display: none;margin-top:10px;" id="loadPostsAndProductsBtn" class="button button-primary">Index Post And Products</button>
    <button style="display: none;margin-top:10px;" id="loadProductsOnlyBtn" class="button button-primary">Index Products Only</button>
    <button style="display: none;margin-top:10px;" type="button" class="button button-secondary" id="loadUserDataBtn">Load Your Data</button>
    <button style="display: none;margin-top:10px;" type="button" class="button button-secondary" id="stopLoad">Stop Load</button>
    <div id="loadingSpinner" style="display: none;">
        <div class="loadSpinner"></div>
    </div>
    <div id="progress-container">
        <h1 class="center-item">Indexing Your Pages</h1>
        <progress id="progressBar" max="100" value="0" style="width: 100%;"></progress>
        <div id="progressLabel"></div>
    </div>
    <div id="hdy-demo-container" style="display:none;">You can take a look at your demo search result:<a id="hdy_demo">Click here</a></div>
</div>
