(window.webpackJsonp=window.webpackJsonp||[]).push([["chunk-fd1f7"],{CgKs:function(t,e,a){},nRuC:function(t,e,a){"use strict";var i=a("CgKs");a.n(i).a},nm8A:function(t,e,a){"use strict";a.r(e);var i={name:"AchievementCountStatistics",mixins:[a("zq7i").a],data:function(){return{}},computed:{},created:function(){this.type="count"},methods:{}},n=(a("nRuC"),a("KHd+")),s=Object(n.a)(i,function(){var t=this,e=t.$createElement,a=t._self._c||e;return a("div",{directives:[{name:"loading",rawName:"v-loading",value:t.loading,expression:"loading"}],staticClass:"main-container"},[a("filtrate-handle-view",{staticClass:"filtrate-bar",attrs:{"show-year-select":!0,title:"合同数量分析","module-type":"contract"},on:{load:function(e){t.loading=!0},change:t.getDataList}}),t._v(" "),a("div",{staticClass:"content"},[t._m(0),t._v(" "),a("div",{staticClass:"table-content"},[a("div",{staticClass:"handle-bar"},[a("el-button",{staticClass:"export-btn",on:{click:t.exportClick}},[t._v("导出")])],1),t._v(" "),a("el-table",{attrs:{data:t.list,height:"180",stripe:"",border:"","highlight-current-row":""}},t._l(t.fieldList,function(t,e){return a("el-table-column",{key:e,attrs:{fixed:0==e,"min-width":0==e?180:100,prop:t.field,label:t.name,align:"center","header-align":"center","show-overflow-tooltip":""}})}))],1)])],1)},[function(){var t=this.$createElement,e=this._self._c||t;return e("div",{staticClass:"axis-content"},[e("div",{attrs:{id:"axismain"}})])}],!1,null,"33929586",null);s.options.__file="AchievementCountStatistics.vue";e.default=s.exports},zq7i:function(t,e,a){"use strict";var i=a("31UX"),n=a("MT78"),s=a.n(n),o=a("vwqx");e.a={data:function(){return{axisOption:null,loading:!1,postParams:{},list:[],fieldList:[],type:"",typeName:"",typeUnit:""}},components:{},mixins:[i.a],props:{},computed:{},watch:{},mounted:function(){"back"==this.type?(this.typeName="回款金额",this.typeUnit="(元)"):"count"==this.type?(this.typeName="合同数量",this.typeUnit="（个）"):"money"==this.type&&(this.typeName="合同金额",this.typeUnit="(元)"),this.initAxis()},methods:{getDataList:function(t){var e=this;this.loading=!0,t.type=this.type,this.postParams=t,Object(o.a)(this.postParams).then(function(t){e.loading=!1;for(var a=[{name:"当月"+e.typeName+e.typeUnit},{name:"环比增长（%）"},{name:"同比增长（%）"}],i=[{field:"name",name:"日期"}],n=0;n<t.data.length;n++){var s=t.data[n],o="value"+n;i.length<=t.data.length&&i.push({field:o,name:s.month.toString()});for(var l=["thisMonth","chain_ratio","year_on_year"],r=0;r<l.length;r++){var c=l[r];a[r][o]=s[c]}}e.fieldList=i,e.list=a;for(var p=[],h=[],d=[],m=[],u=[],x=[],y=0;y<t.data.length;y++){var f=t.data[y];p.push(f.thisMonth),h.push(f.lastMonth),d.push(f.lastYear),m.push(f.chain_ratio),u.push(f.year_on_year),x.push(f.month)}e.axisOption.xAxis[0].data=x,e.axisOption.series[0].data=p,e.axisOption.series[1].data=m,e.axisOption.series[2].data=u,e.chartObj.setOption(e.axisOption,!0)}).catch(function(){e.loading=!1})},initAxis:function(){this.chartObj=s.a.init(document.getElementById("axismain")),this.axisOption={color:["#6CA2FF","#6AC9D7","#72DCA2","#DBB375","#E164F7","#FF7474","#FFB270","#FECD51"],toolbox:this.toolbox,tooltip:{trigger:"axis",axisPointer:{type:"shadow"}},legend:{data:["当月"+this.typeName,"环比增长","同比增长"],bottom:"0px",itemWidth:14},grid:{top:"50px",left:"30px",right:"30px",bottom:"40px",containLabel:!0},xAxis:[{type:"category",data:[],axisTick:{show:!1},axisLabel:{color:"#333"},axisLine:{onZero:!0,onZeroAxisIndex:1,lineStyle:{color:"#333"}},splitLine:{show:!0,lineStyle:{color:"#e6e6e6"}}}],yAxis:[{type:"value",name:this.typeUnit,axisTick:{show:!1},axisLabel:{color:"#333",formatter:"{value}"},axisLine:{lineStyle:{color:"#333"}},splitLine:{show:!0,lineStyle:{color:"#e6e6e6"}}},{type:"value",name:"",axisTick:{alignWithLabel:!0,lineStyle:{width:0}},axisLabel:{color:"#333",formatter:"{value}%"},axisLine:{lineStyle:{color:"#333"}},splitLine:{show:!0,lineStyle:{color:"#e6e6e6"}}}],series:[{name:"当月"+this.typeName,type:"bar",yAxisIndex:0,barMaxWidth:10,markPoint:{data:[{type:"max",name:"最大值"},{type:"min",name:"最小值"}]},data:[]},{name:"环比增长",type:"line",yAxisIndex:1,markLine:{data:[{type:"average",name:"平均值"}]},markPoint:{data:[{type:"max",name:"最大值"},{type:"min",name:"最小值"}]},data:[]},{name:"同比增长",type:"line",yAxisIndex:1,markLine:{data:[{type:"average",name:"平均值"}]},markPoint:{data:[{type:"max",name:"最大值"},{type:"min",name:"最小值"}]},data:[]}]},this.chartObj.setOption(this.axisOption,!0)},exportClick:function(){this.requestExportInfo(o.b,this.postParams,"analysis")}},deactivated:function(){}}}}]);