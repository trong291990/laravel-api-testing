var Vue = require('vue');

Vue.config.delimiters = ['(%', '%)'];

new Vue({
    el: '#app',
    data: {
        test: 'yolo'
    }
})