(window.webpackJsonp=window.webpackJsonp||[]).push([["chunk-bb61"],{"2bXq":function(t,e,i){"use strict";var a=i("EP+0"),s=i("MT78"),r=i.n(s),n=i("UxrY"),o=i.n(n),l={name:"StatisticalOverview",components:{CreateSections:a.a,RadialProgressBar:o.a},props:{data:{type:Object,default:function(){return{allNum:0,archiveNum:0,completionRate:0,delayRate:0,doneNum:0,overtimeNum:0,undoneNum:0}}},list:Array},data:function(){return{barOption:null,barChart:null}},computed:{showList:function(){return this.list&&this.list.length>3?this.list.slice(0,3):this.list||[]}},watch:{data:function(){this.changeBarData()}},mounted:function(){this.initBar(),this.changeBarData()},methods:{changeBarData:function(){this.barOption.series[0].data=[this.data.allNum||0,this.data.undoneNum||0,this.data.doneNum||0,this.data.overtimeNum||0,this.data.archiveNum||0],this.barChart.setOption(this.barOption,!0)},initBar:function(){this.barChart=r.a.init(document.getElementById("barmain")),this.barOption={tooltip:{show:!1},legend:{show:!1},grid:{top:"15px",left:0,right:0,bottom:"10px",containLabel:!0},xAxis:{type:"category",data:["总任务","未完成","已完成","已逾期","已归档"],axisTick:{alignWithLabel:!0,lineStyle:{width:0}},axisLabel:{color:"#666"},axisLine:{lineStyle:{color:"#ECECEC"}},splitLine:{show:!1}},yAxis:{show:!1},series:[{name:"成交客户数",type:"bar",barMaxWidth:10,label:{show:!0,position:"top",color:"#333"},itemStyle:{barBorderRadius:[7.5,7.5,0,0]},color:function(t){return["#0067E5","#0067E5","#0067E5","#FF5D60","#19DBC1"][t.dataIndex]},data:[]}]}}}},c=(i("QHFv"),i("KHd+")),h=Object(c.a)(l,function(){var t=this,e=t.$createElement,i=t._self._c||e;return i("create-sections",{attrs:{title:"任务总览"}},[i("flexbox",{staticClass:"content"},[i("div",{staticClass:"content-progress"},[i("radial-progress-bar",{staticClass:"progress",attrs:{diameter:120,"completed-steps":parseFloat(t.data.completionRate)||0,"total-steps":100,"stroke-width":7,"inner-stroke-color":"#E7F2FA","start-color":"#0067E5","stop-color":"#0067E5"}},[i("p",{staticClass:"progress-title"},[t._v("完成率")]),t._v(" "),i("p",{staticClass:"progress-value"},[t._v(t._s(t.data.completionRate||0)),i("span",[t._v("%")])])]),t._v(" "),i("radial-progress-bar",{staticClass:"progress",attrs:{diameter:120,"completed-steps":parseFloat(t.data.delayRate)||0,"total-steps":100,"stroke-width":7,"inner-stroke-color":"#E8F2FA","start-color":"#FF5D60","stop-color":"#FF5D60"}},[i("p",{staticClass:"progress-title"},[t._v("逾期率")]),t._v(" "),i("p",{staticClass:"progress-value"},[t._v(t._s(t.data.delayRate||0)),i("span",[t._v("%")])])])],1),t._v(" "),i("div",{staticClass:"content-bar"},[i("div",{attrs:{id:"barmain"}})]),t._v(" "),t.list&&t.list.length>0?i("div",{staticClass:"content-user"},[i("div",{staticClass:"content-user-items"},[t._l(t.showList,function(e,a){return i("div",{key:a,staticClass:"main-user"},[i("xr-avatar",{staticClass:"main-user-head",attrs:{name:e.realname,size:36,src:e.img||e.thumb_img}}),t._v(" "),i("div",{staticClass:"main-user-name"},[t._v(t._s(e.realname))])],1)}),t._v(" "),t.list.length>3?i("el-tooltip",{attrs:{placement:"top",effect:"light","popper-class":"tooltip-change-border task-tooltip"}},[i("div",{staticClass:"tooltip-content",staticStyle:{margin:"10px 10px 10px 0"},attrs:{slot:"content"},slot:"content"},t._l(t.list,function(e,a){return i("div",{key:a,staticClass:"item-label",staticStyle:{display:"inline-block","margin-right":"10px"}},[a>2?i("span",{staticClass:"k-name"},[t._v(t._s(e.realname))]):t._e()])})),t._v(" "),i("span",{staticClass:"main-user-more"},[i("i",[t._v("...")])])]):t._e()],2),t._v(" "),i("div",{staticClass:"content-user-title"},[t._v("项目负责人")])]):t._e()]),t._v(" "),t._t("default")],2)},[],!1,null,"05d18402",null);h.options.__file="StatisticalOverview.vue";e.a=h.exports},"2f65":function(t,e,i){},"6XJn":function(t,e,i){},"73Ci":function(t,e,i){},"7mY3":function(t,e,i){"use strict";var a=i("2f65");i.n(a).a},"9GgJ":function(t,e,i){"use strict";var a={name:"XrHeader",components:{},props:{iconClass:[String,Array],iconColor:String,label:String,showSearch:{type:Boolean,default:!1},searchIconType:{type:String,default:"text"},placeholder:{type:String,default:"请输入内容"},ftTop:{type:String,default:"15px"},content:[String,Number],inputAttr:{type:Object,default:function(){}}},data:function(){return{search:""}},computed:{},watch:{content:{handler:function(){this.search!=this.content&&(this.search=this.content)},immediate:!0}},mounted:function(){},beforeDestroy:function(){},methods:{inputChange:function(){this.$emit("update:content",this.search)},searchClick:function(){this.$emit("search",this.search)}}},s=(i("7mY3"),i("KHd+")),r=Object(s.a)(a,function(){var t=this,e=t.$createElement,i=t._self._c||e;return i("flexbox",{staticClass:"xr-header"},[t.iconClass?i("div",{staticClass:"xr-header__icon",style:{backgroundColor:t.iconColor}},[i("i",{class:t.iconClass})]):t._e(),t._v(" "),i("div",{staticClass:"xr-header__label"},[t.$slots.label?t._t("label"):[t._v(t._s(t.label))]],2),t._v(" "),t.showSearch?i("el-input",t._b({staticClass:"xr-header__search",class:{"is-text":"text"===t.searchIconType},style:{"margin-top":t.ftTop},attrs:{placeholder:t.placeholder},on:{input:t.inputChange},nativeOn:{keyup:function(e){return"button"in e||!t._k(e.keyCode,"enter",13,e.key,"Enter")?t.searchClick(e):null}},model:{value:t.search,callback:function(e){t.search=e},expression:"search"}},"el-input",t.inputAttr,!1),["text"===t.searchIconType?i("el-button",{attrs:{slot:"append",type:"primary"},nativeOn:{click:function(e){return t.searchClick(e)}},slot:"append"},[t._v("搜索")]):i("el-button",{attrs:{slot:"append",icon:"el-icon-search"},nativeOn:{click:function(e){return t.searchClick(e)}},slot:"append"})],1):t._e(),t._v(" "),i("div",{staticClass:"xr-header__ft",style:{top:t.ftTop}},[t._t("ft")],2)],1)},[],!1,null,"acb6d756",null);r.options.__file="index.vue";e.a=r.exports},QHFv:function(t,e,i){"use strict";var a=i("xTz6");i.n(a).a},UxrY:function(t,e,i){t.exports=i("u1YS")},W5iH:function(t,e,i){"use strict";var a={name:"StatisticalMember",components:{CreateSections:i("EP+0").a},props:{list:Array},data:function(){return{fieldList:[{prop:"realname",label:"姓名"},{prop:"allCount",label:"任务总数"},{prop:"doneCount",label:"已完成数"},{prop:"undoneCount",label:"未完成数"},{prop:"overtimeCount",label:"逾期数"},{prop:"completionRate",label:"完成率"}]}},computed:{},mounted:function(){},methods:{cellStyle:function(t){var e=t.row,i=t.column;t.rowIndex,t.columnIndex;return"overtimeCount"===i.property&&e.overtimeCount?{color:"#FF5D60"}:"completionRate"===i.property&&e.completionRate?{color:"#19DBC1"}:{}}}},s=(i("uMuW"),i("KHd+")),r=Object(s.a)(a,function(){var t=this.$createElement,e=this._self._c||t;return e("create-sections",{attrs:{title:"成员完成情况"}},[e("el-table",{staticClass:"table",staticStyle:{width:"100%"},attrs:{data:this.list,"cell-style":this.cellStyle,height:"500",stripe:"",border:"","highlight-current-row":""}},this._l(this.fieldList,function(t,i){return e("el-table-column",{key:i,attrs:{prop:t.prop,label:t.label,"show-overflow-tooltip":""}})}))],1)},[],!1,null,"1c0750fe",null);r.options.__file="StatisticalMember.vue";e.a=r.exports},dGSA:function(t,e,i){"use strict";var a=i("EP+0"),s=i("MT78"),r=i.n(s),n={name:"StatisticalTask",components:{CreateSections:a.a},props:{title:{type:String,default:""},type:String,list:{type:Array,default:function(){return[]}}},data:function(){return{barOption:null,barChart:null,id:Math.ceil(100*Math.random())}},computed:{},watch:{list:function(){this.changeBarData()}},mounted:function(){this.initBar(),this.changeBarData()},methods:{changeBarData:function(){for(var t=[],e=[],i=[],a=0;a<this.list.length;a++){var s=this.list[a];"task"==this.type?(t.push(s.name),e.push(s.undoneTask),i.push(s.doneTask)):"label"==this.type&&(t.push(s.lablename),e.push(s.undoneTask),i.push(s.doneTask))}this.barOption.xAxis[0].data=t,this.barOption.series[0].data=i,this.barOption.series[1].data=e,this.barChart.setOption(this.barOption,!0)},initBar:function(){this.barChart=r.a.init(document.getElementById("barmain"+this.id)),this.barOption={color:["#6ca2ff","#ff7474"],tooltip:{trigger:"axis",axisPointer:{type:"shadow"}},legend:{data:["已完成","未完成"],bottom:"0px",itemWidth:14},grid:{top:"20px",left:"20px",right:"20px",bottom:"30px",containLabel:!0},xAxis:[{type:"category",data:[],axisTick:{alignWithLabel:!0,lineStyle:{width:0}},axisLabel:{color:"#666"},axisLine:{lineStyle:{color:"#ECECEC"}},splitLine:{show:!1}}],yAxis:{splitNumber:3,axisLine:{show:!1},axisTick:{show:!1},splitLine:{lineStyle:{color:"#ECECEC"}},axisLabel:{textStyle:{color:"#666"}}},series:[{name:"已完成",type:"bar",stack:"one",barWidth:"15%",data:[]},{name:"未完成",type:"bar",stack:"one",barWidth:"15%",data:[]}]}}}},o=(i("nzhU"),i("KHd+")),l=Object(o.a)(n,function(){var t=this.$createElement,e=this._self._c||t;return e("create-sections",{attrs:{title:this.title}},[e("div",{staticClass:"barmain",attrs:{id:"barmain"+this.id}})])},[],!1,null,"491e9007",null);l.options.__file="StatisticalTask.vue";e.a=l.exports},hb9p:function(t,e,i){"use strict";var a=i("6XJn");i.n(a).a},nzhU:function(t,e,i){"use strict";var a=i("73Ci");i.n(a).a},qedL:function(t,e,i){},u1YS:function(t,e,i){"use strict";i.r(e);var a={props:{diameter:{type:Number,required:!1,default:200},totalSteps:{type:Number,required:!0,default:10},completedSteps:{type:Number,required:!0,default:0},startColor:{type:String,required:!1,default:"#bbff42"},stopColor:{type:String,required:!1,default:"#429321"},strokeWidth:{type:Number,required:!1,default:10},animateSpeed:{type:Number,required:!1,default:1e3},innerStrokeColor:{type:String,required:!1,default:"#323232"},fps:{type:Number,required:!1,default:60},timingFunc:{type:String,required:!1,default:"linear"}},data:()=>({gradient:{fx:.99,fy:.5,cx:.5,cy:.5,r:.65},gradientAnimation:null,currentAngle:0,strokeDashoffset:0}),computed:{radius(){return this.diameter/2},circumference(){return Math.PI*this.innerCircleDiameter},stepSize(){return 0===this.totalSteps?0:100/this.totalSteps},finishedPercentage(){return this.stepSize*this.completedSteps},circleSlice(){return 2*Math.PI/this.totalSteps},animateSlice(){return this.circleSlice/this.totalPoints},innerCircleDiameter(){return this.diameter-2*this.strokeWidth},innerCircleRadius(){return this.innerCircleDiameter/2},totalPoints(){return this.animateSpeed/this.animationIncrements},animationIncrements(){return 1e3/this.fps},hasGradient(){return this.startColor!==this.stopColor},containerStyle(){return{height:`${this.diameter}px`,width:`${this.diameter}px`}},progressStyle(){return{height:`${this.diameter}px`,width:`${this.diameter}px`,strokeWidth:`${this.strokeWidth}px`,strokeDashoffset:this.strokeDashoffset,transition:`stroke-dashoffset ${this.animateSpeed}ms ${this.timingFunc}`}},strokeStyle(){return{height:`${this.diameter}px`,width:`${this.diameter}px`,strokeWidth:`${this.strokeWidth}px`}},innerCircleStyle(){return{width:`${this.innerCircleDiameter}px`}}},methods:{getStopPointsOfCircle(t){const e=[];for(let i=0;i<t;i++){const t=this.circleSlice*i;e.push(this.getPointOfCircle(t))}return e},getPointOfCircle:t=>({x:.5+.5*Math.cos(t),y:.5+.5*Math.sin(t)}),gotoPoint(){const t=this.getPointOfCircle(this.currentAngle);this.gradient.fx=t.x,this.gradient.fy=t.y},changeProgress({isAnimate:t=!0}){if(this.strokeDashoffset=(100-this.finishedPercentage)/100*this.circumference,this.gradientAnimation&&clearInterval(this.gradientAnimation),!t)return void this.gotoNextStep();const e=(this.completedSteps-1)*this.circleSlice;let i=(this.currentAngle-e)/this.animateSlice;const a=Math.abs(i-this.totalPoints)/this.totalPoints,s=i<this.totalPoints;this.gradientAnimation=setInterval(()=>{s&&i>=this.totalPoints||!s&&i<this.totalPoints?clearInterval(this.gradientAnimation):(this.currentAngle=e+this.animateSlice*i,this.gotoPoint(),i+=s?a:-a)},this.animationIncrements)},gotoNextStep(){this.currentAngle=this.completedSteps*this.circleSlice,this.gotoPoint()}},watch:{totalSteps(){this.changeProgress({isAnimate:!0})},completedSteps(){this.changeProgress({isAnimate:!0})},diameter(){this.changeProgress({isAnimate:!0})},strokeWidth(){this.changeProgress({isAnimate:!0})}},created(){this.changeProgress({isAnimate:!1})}},s=(i("hb9p"),i("KHd+")),r=Object(s.a)(a,function(){var t=this,e=t.$createElement,i=t._self._c||e;return i("div",{staticClass:"radial-progress-container",style:t.containerStyle},[i("div",{staticClass:"radial-progress-inner",style:t.innerCircleStyle},[t._t("default")],2),t._v(" "),i("svg",{staticClass:"radial-progress-bar",attrs:{width:t.diameter,height:t.diameter,version:"1.1",xmlns:"http://www.w3.org/2000/svg"}},[i("defs",[i("radialGradient",{attrs:{id:"radial-gradient"+t._uid,fx:t.gradient.fx,fy:t.gradient.fy,cx:t.gradient.cx,cy:t.gradient.cy,r:t.gradient.r}},[i("stop",{attrs:{offset:"30%","stop-color":t.startColor}}),t._v(" "),i("stop",{attrs:{offset:"100%","stop-color":t.stopColor}})],1)],1),t._v(" "),i("circle",{style:t.strokeStyle,attrs:{r:t.innerCircleRadius,cx:t.radius,cy:t.radius,fill:"transparent",stroke:t.innerStrokeColor,"stroke-dasharray":t.circumference,"stroke-dashoffset":"0","stroke-linecap":"round"}}),t._v(" "),i("circle",{style:t.progressStyle,attrs:{transform:"rotate(270, "+t.radius+","+t.radius+")",r:t.innerCircleRadius,cx:t.radius,cy:t.radius,fill:"transparent",stroke:"url(#radial-gradient"+t._uid+")","stroke-dasharray":t.circumference,"stroke-dashoffset":t.circumference,"stroke-linecap":"round"}})])])},[],!1,null,null,null);r.options.__file="RadialProgressBar.vue";e.default=r.exports},uMuW:function(t,e,i){"use strict";var a=i("qedL");i.n(a).a},xTz6:function(t,e,i){},zvRq:function(t,e,i){"use strict";i.d(e,"a",function(){return s});var a=i("t3Un");function s(t){return Object(a.a)({url:"work/work/statistic",method:"post",data:t})}}}]);