(window.webpackJsonp=window.webpackJsonp||[]).push([["chunk-1956"],{Jy0J:function(t,e,i){},ZrLL:function(t,e,i){"use strict";var a=i("Jy0J");i.n(a).a},nd5u:function(t,e,i){"use strict";i.r(e);var a=i("JrBc"),n=i("MT78"),s=i.n(n),o=i("pHUW"),l={name:"RankingContractStatistics",mixins:[a.a],data:function(){return{}},computed:{},mounted:function(){this.fieldList=[{field:"user_name",name:"签订人"},{field:"structure_name",name:"部门"},{field:"money",name:"合同金额（元）"}],this.initAxis()},methods:{getDataList:function(t){var e=this;this.postParams=t,this.loading=!0,Object(o.e)(t).then(function(t){e.loading=!1,e.list=t.data||[];for(var i=[],a=[],n=t.data.length>10?10:t.data.length,s=0;s<n;s++){var o=t.data[s];i.splice(0,0,parseFloat(o.money)),a.splice(0,0,o.user_name)}e.axisOption.yAxis[0].data=a,e.axisOption.series[0].data=i,e.chartObj.setOption(e.axisOption,!0)}).catch(function(){e.loading=!1})},initAxis:function(){this.chartObj=s.a.init(document.getElementById("axismain")),this.chartObj.setOption(this.axisOption,!0)},exportClick:function(){this.requestExportInfo(o.f,this.postParams,"contract")}}},r=(i("ZrLL"),i("KHd+")),c=Object(r.a)(l,function(){var t=this,e=t.$createElement,i=t._self._c||e;return i("div",{directives:[{name:"loading",rawName:"v-loading",value:t.loading,expression:"loading"}],staticClass:"main-container"},[i("filtrate-handle-view",{staticClass:"filtrate-bar",attrs:{"show-user-select":!1,title:"合同金额排行","module-type":"ranking"},on:{load:function(e){t.loading=!0},change:t.getDataList}}),t._v(" "),i("div",{staticClass:"content"},[i("div",{staticClass:"content-title"},[t._v("合同金额排行（按实际下单时间）")]),t._v(" "),i("div",{directives:[{name:"empty",rawName:"v-empty",value:0===t.list.length,expression:"list.length === 0"}],staticClass:"axis-content",attrs:{"xs-empty-text":"暂无排行"}},[i("div",{attrs:{id:"axismain"}})]),t._v(" "),i("div",{staticClass:"table-content"},[i("div",{staticClass:"handle-bar"},[i("el-button",{staticClass:"export-btn",on:{click:t.exportClick}},[t._v("导出")])],1),t._v(" "),i("el-table",{attrs:{data:t.list,height:"400",stripe:"",border:"","highlight-current-row":""}},[i("el-table-column",{attrs:{align:"center","header-align":"center","show-overflow-tooltip":"",label:"公司总排名"},scopedSlots:t._u([{key:"default",fn:function(e){return[t._v("\n            "+t._s(e.$index+1)+"\n          ")]}}])}),t._v(" "),t._l(t.fieldList,function(t,e){return i("el-table-column",{key:e,attrs:{prop:t.field,label:t.name,align:"center","header-align":"center","show-overflow-tooltip":""}})})],2)],1)])],1)},[],!1,null,"55683cf6",null);c.options.__file="RankingContractStatistics.vue";e.default=c.exports}}]);