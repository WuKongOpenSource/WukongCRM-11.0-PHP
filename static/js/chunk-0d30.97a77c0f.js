(window.webpackJsonp=window.webpackJsonp||[]).push([["chunk-0d30"],{"9kPm":function(t,n,e){"use strict";n.a={data:function(){return{showTable:!0}},methods:{mixinSortFn:function(){var t=arguments.length>0&&void 0!==arguments[0]?arguments[0]:[],n=arguments.length>1&&void 0!==arguments[1]?arguments[1]:null,e=arguments.length>2&&void 0!==arguments[2]?arguments[2]:"ascending";if("[object Array]"!==Object.prototype.toString.call(t))return[];if(!n)return t;t.sort(function(t,a){if(t[n]===a[n])return 0;var i=isNaN(Number(t[n]))||isNaN(Number(a[n]))?t[n]<a[n]:Number(t[n])<Number(a[n]);return"descending"===e?i?1:-1:i?-1:1})}}}},SvN8:function(t,n,e){},cptc:function(t,n,e){"use strict";var a=e("SvN8");e.n(a).a},"f+T/":function(t,n,e){"use strict";e.r(n);var a=e("9kPm"),i=e("31UX"),o=e("vwqx"),s={name:"AchievementSummaryStatistics",components:{FiltrateHandleView:e("CMIa").a},mixins:[i.a,a.a],data:function(){return{loading:!1,tableHeight:document.documentElement.clientHeight-240,postParams:{},list:[],data:{back_zong:0,count_zong:0,money_zong:0,w_back_zong:0},fieldList:[{field:"type",name:"日期"},{field:"count",name:"签约合同数（个）"},{field:"money",name:"签约合同金额（元）"},{field:"back",name:"回款金额（元）"}]}},computed:{},mounted:function(){var t=this;window.onresize=function(){t.tableHeight=document.documentElement.clientHeight-240}},methods:{getDataList:function(t){var n=this;this.postParams=t,this.loading=!0,Object(o.e)(t).then(function(t){var e=t.data;n.data={back_zong:e.back_zong,count_zong:e.count_zong,money_zong:e.money_zong,w_back_zong:e.w_back_zong},n.list=e.list||[],n.loading=!1}).catch(function(){n.loading=!1})},exportClick:function(){this.requestExportInfo(o.f,this.postParams,"summary")}}},c=(e("cptc"),e("KHd+")),r=Object(c.a)(s,function(){var t=this,n=t.$createElement,e=t._self._c||n;return e("div",{directives:[{name:"loading",rawName:"v-loading",value:t.loading,expression:"loading"}],staticClass:"main-container"},[e("filtrate-handle-view",{staticClass:"filtrate-bar",attrs:{"show-year-select":!0,title:"合同汇总表","module-type":"contract"},on:{load:function(n){t.loading=!0},change:t.getDataList}},[e("el-button",{staticClass:"export-button",attrs:{type:"primary"},nativeOn:{click:function(n){return t.exportClick(n)}}},[t._v("导出")])],1),t._v(" "),e("div",{staticClass:"content"},[e("div",{staticClass:"content-title"},[t._v("\n      签约合同数："+t._s(t.data.count_zong)+"个；签约合同金额："),e("span",{staticClass:"special"},[t._v(t._s(t.data.money_zong))]),t._v("元；回款金额："),e("span",{staticClass:"special"},[t._v(t._s(t.data.back_zong))]),t._v("元；未收款金额："),e("span",{staticClass:"special"},[t._v(t._s(t.data.w_back_zong))]),t._v("元")]),t._v(" "),e("div",{staticClass:"table-content"},[t.showTable?e("el-table",{attrs:{data:t.list,height:t.tableHeight,stripe:"",border:"","highlight-current-row":""},on:{"sort-change":function(n){var e=n.prop,a=n.order;return t.mixinSortFn(t.list,e,a)}}},t._l(t.fieldList,function(t,n){return e("el-table-column",{key:n,attrs:{prop:t.field,label:t.name,sortable:"custom",align:"center","header-align":"center","show-overflow-tooltip":""}})})):t._e()],1)])],1)},[],!1,null,"550fba21",null);r.options.__file="AchievementSummaryStatistics.vue";n.default=r.exports}}]);