(window.webpackJsonp=window.webpackJsonp||[]).push([["chunk-0634"],{"1a2u":function(t,e,i){"use strict";var n=i("HrRu");i.n(n).a},"2f65":function(t,e,i){},"7mY3":function(t,e,i){"use strict";var n=i("2f65");i.n(n).a},"9GgJ":function(t,e,i){"use strict";var n={name:"XrHeader",components:{},props:{iconClass:[String,Array],iconColor:String,label:String,showSearch:{type:Boolean,default:!1},searchIconType:{type:String,default:"text"},placeholder:{type:String,default:"请输入内容"},ftTop:{type:String,default:"15px"},content:[String,Number],inputAttr:{type:Object,default:function(){}}},data:function(){return{search:""}},computed:{},watch:{content:{handler:function(){this.search!=this.content&&(this.search=this.content)},immediate:!0}},mounted:function(){},beforeDestroy:function(){},methods:{inputChange:function(){this.$emit("update:content",this.search)},searchClick:function(){this.$emit("search",this.search)}}},a=(i("7mY3"),i("KHd+")),o=Object(a.a)(n,function(){var t=this,e=t.$createElement,i=t._self._c||e;return i("flexbox",{staticClass:"xr-header"},[t.iconClass?i("div",{staticClass:"xr-header__icon",style:{backgroundColor:t.iconColor}},[i("i",{class:t.iconClass})]):t._e(),t._v(" "),i("div",{staticClass:"xr-header__label"},[t.$slots.label?t._t("label"):[t._v(t._s(t.label))]],2),t._v(" "),t.showSearch?i("el-input",t._b({staticClass:"xr-header__search",class:{"is-text":"text"===t.searchIconType},style:{"margin-top":t.ftTop},attrs:{placeholder:t.placeholder},on:{input:t.inputChange},nativeOn:{keyup:function(e){return"button"in e||!t._k(e.keyCode,"enter",13,e.key,"Enter")?t.searchClick(e):null}},model:{value:t.search,callback:function(e){t.search=e},expression:"search"}},"el-input",t.inputAttr,!1),["text"===t.searchIconType?i("el-button",{attrs:{slot:"append",type:"primary"},nativeOn:{click:function(e){return t.searchClick(e)}},slot:"append"},[t._v("搜索")]):i("el-button",{attrs:{slot:"append",icon:"el-icon-search"},nativeOn:{click:function(e){return t.searchClick(e)}},slot:"append"})],1):t._e(),t._v(" "),i("div",{staticClass:"xr-header__ft",style:{top:t.ftTop}},[t._t("ft")],2)],1)},[],!1,null,"acb6d756",null);o.options.__file="index.vue";e.a=o.exports},HrRu:function(t,e,i){},IWDt:function(t,e,i){},KWCB:function(t,e,i){"use strict";var n=i("n9M2");i.n(n).a},gqmq:function(t,e,i){"use strict";i.r(e);var n=i("Rb0w"),a={name:"JurisdictionCreate",components:{},props:{show:{type:Boolean,default:!1},action:{type:Object,default:function(){return{type:"save"}}}},data:function(){return{loading:!1,title:"",remark:"",showTreeData:[],defaultProps:{children:"children",label:"title"}}},computed:{diaTitle:function(){return"save"==this.action.type?"新建":"编辑"}},watch:{show:function(t){t&&this.initInfo()}},mounted:function(){},methods:{initInfo:function(){"update"==this.action.type?(this.title=this.action.data.title,this.remark=this.action.data.remark):(this.title="",this.remark="",this.$refs.tree&&this.$refs.tree.setCheckedKeys([])),0==this.showTreeData.length?this.getRulesList():this.checkTreeByUpdateInfo()},getRulesList:function(){var t=this;this.loading=!0,Object(n.a)({type:"tree",pid:5}).then(function(e){t.showTreeData=e.data?[e.data]:[],t.checkTreeByUpdateInfo(),t.loading=!1}).catch(function(){t.loading=!1})},checkTreeByUpdateInfo:function(){var t=this;this.$nextTick(function(){if(t.$refs.tree){"update"==t.action.type&&t.$refs.tree.setCheckedKeys(t.getUserModuleRules(t.action.data.rules));var e=t.$refs.tree.$children&&t.$refs.tree.$children.length?t.$refs.tree.$children[0].$el:null;e&&(e=e.children&&e.children.length?e.children[0]:null)&&(e.style.display="none")}})},sureClick:function(){var t=this;if(this.title){this.loading=!0;var e=this.$refs.tree.getCheckedKeys(),i={title:this.title,remark:this.remark,rules:e.join(","),pid:5};"update"==this.action.type&&(i.id=this.action.data.id),Object(n.d)(i).then(function(e){t.loading=!1,t.$emit("submite"),t.closeView()}).catch(function(){t.loading=!1})}else this.$message.error("请填写权限名称")},closeView:function(){this.$emit("update:show",!1)},getUserModuleRules:function(t){t||(t=[]);for(var e=this.showTreeData[0],i=!1,n=this.copyItem(t),a=0;a<e.children.length;a++){var o=e.children[a];o.children||(o.children=[]);for(var s=0;s<t.length;s++){for(var r=t[s],l=[],c=0;c<o.children.length;c++){var d=o.children[c];d.id==r&&l.push(d)}l.length!=o.children.length&&(i=!0,this.removeItem(n,o.id))}}i&&this.removeItem(n,e.id);for(var u=[],h=0;h<n.length;h++){var p=n[h];p&&u.push(parseInt(p))}return u},copyItem:function(t){for(var e=[],i=0;i<t.length;i++)e.push(t[i]);return e},removeItem:function(t,e){for(var i=-1,n=0;n<t.length;n++)if(e==t[n]){i=n;break}i>0&&t.splice(i,1)},containItem:function(t,e){for(var i=0;i<t.length;i++)if(e==t[i])return!0;return!1}}},o=(i("ieO5"),i("KHd+")),s=Object(o.a)(a,function(){var t=this,e=t.$createElement,i=t._self._c||e;return i("el-dialog",{directives:[{name:"loading",rawName:"v-loading",value:t.loading,expression:"loading"}],staticClass:"create-dialog",attrs:{title:t.diaTitle,visible:t.show,"modal-append-to-body":!0,"append-to-body":!0,"close-on-click-modal":!1,width:"700px"},on:{close:t.closeView}},[i("div",{staticClass:"label-input"},[i("label",{staticClass:"label-title"},[t._v("权限名称")]),t._v(" "),i("el-input",{attrs:{maxlength:100,placeholder:"请输入权限名称"},model:{value:t.title,callback:function(e){t.title=e},expression:"title"}})],1),t._v(" "),i("div",{staticClass:"label-input"},[i("label",{staticClass:"label-title"},[t._v("权限描述")]),t._v(" "),i("el-input",{attrs:{rows:2,maxlength:300,type:"textarea",placeholder:"请输入权限描述"},model:{value:t.remark,callback:function(e){t.remark=e},expression:"remark"}})],1),t._v(" "),i("label",{staticClass:"label-title"},[t._v("权限配置")]),t._v(" "),i("div",{staticClass:"jurisdiction-content-checkbox"},[i("el-tree",{ref:"tree",staticStyle:{height:"0"},attrs:{data:t.showTreeData,indent:0,"expand-on-click-node":!1,props:t.defaultProps,"show-checkbox":"","node-key":"id","empty-text":"","default-expand-all":""}})],1),t._v(" "),i("span",{staticClass:"dialog-footer",attrs:{slot:"footer"},slot:"footer"},[i("el-button",{attrs:{type:"primary"},on:{click:t.sureClick}},[t._v("确 定")]),t._v(" "),i("el-button",{on:{click:t.closeView}},[t._v("取 消")])],1)])},[],!1,null,"29a78d44",null);s.options.__file="JurisdictionCreate.vue";var r=s.exports,l=i("jzeO"),c=i("9GgJ"),d={name:"SystemProject",components:{JurisdictionCreate:r,Reminder:l.a,XrHeader:c.a},mixins:[],data:function(){return{loading:!1,tableHeight:document.documentElement.clientHeight-196,list:[],createAction:{type:"save"},jurisdictionCreateShow:!1}},computed:{},mounted:function(){var t=this;window.onresize=function(){t.tableHeight=document.documentElement.clientHeight-196},this.getList()},methods:{getList:function(){var t=this;this.loading=!0,Object(n.c)().then(function(e){t.list=e.data,t.loading=!1}).catch(function(){t.loading=!1})},addJurisdiction:function(){this.createAction={type:"save"},this.jurisdictionCreateShow=!0},handleRowClick:function(t,e,i){},handleClick:function(t,e){var i=this;"edit"===t?(this.createAction={type:"update",data:e.row},this.jurisdictionCreateShow=!0):"delete"===t&&this.$confirm("删除权限以后，使用了该权限的项目将默认变为只读权限，确认删除?","提示",{confirmButtonText:"确定",cancelButtonText:"取消",type:"warning"}).then(function(){Object(n.b)({id:e.row.id}).then(function(t){i.list.splice(e.$index,1),i.$message({type:"success",message:"操作成功"})}).catch(function(){})}).catch(function(){i.$message({type:"info",message:"已取消删除"})})}}},u=(i("1a2u"),Object(o.a)(d,function(){var t=this,e=t.$createElement,i=t._self._c||e;return i("div",{staticClass:"main"},[i("xr-header",{attrs:{"icon-class":"wk wk-project","icon-color":"#33D08F",label:"自定义项目权限"}}),t._v(" "),i("div",{staticClass:"main-body"},[i("div",{staticClass:"main-table-header"},[i("reminder",{staticClass:"project-reminder",attrs:{content:"为不同场景下的项目成员所需的权限设置匹配的项目、任务列表、任务的操作权限"}}),t._v(" "),i("el-button",{staticClass:"main-table-header-button xr-btn--orange",attrs:{type:"primary",icon:"el-icon-plus"},on:{click:t.addJurisdiction}},[t._v("新建权限")])],1),t._v(" "),i("el-table",{directives:[{name:"loading",rawName:"v-loading",value:t.loading,expression:"loading"}],staticClass:"main-table",staticStyle:{width:"100%"},attrs:{id:"examine-table",data:t.list,height:t.tableHeight,"highlight-current-row":""},on:{"row-click":t.handleRowClick}},[i("el-table-column",{attrs:{"show-overflow-tooltip":"",prop:"title",width:"150",label:"项目权限"}}),t._v(" "),i("el-table-column",{attrs:{"show-overflow-tooltip":"",prop:"remark",label:"项目描述"}}),t._v(" "),i("el-table-column",{attrs:{fixed:"right",label:"操作",width:"100"},scopedSlots:t._u([{key:"default",fn:function(e){return[i("el-button",{attrs:{disabled:1==e.row.status,type:"text",size:"small"},on:{click:function(i){t.handleClick("edit",e)}}},[t._v("编辑")]),t._v(" "),i("el-button",{attrs:{disabled:1==e.row.system,type:"text",size:"small"},on:{click:function(i){t.handleClick("delete",e)}}},[t._v("删除")])]}}])})],1)],1),t._v(" "),i("jurisdiction-create",{attrs:{show:t.jurisdictionCreateShow,action:t.createAction},on:{"update:show":function(e){t.jurisdictionCreateShow=e},submite:t.getList}})],1)},[],!1,null,"a375cfe6",null));u.options.__file="index.vue";e.default=u.exports},ieO5:function(t,e,i){"use strict";var n=i("IWDt");i.n(n).a},jzeO:function(t,e,i){"use strict";var n={name:"Reminder",components:{},props:{closeShow:{type:Boolean,default:!1},content:{type:String,default:"内容"},fontSize:{type:String,default:"13"}},data:function(){return{}},computed:{},mounted:function(){},destroyed:function(){},methods:{close:function(){this.$emit("close")}}},a=(i("KWCB"),i("KHd+")),o=Object(a.a)(n,function(){var t=this,e=t.$createElement,i=t._self._c||e;return i("flexbox",{staticClass:"reminder-wrapper"},[i("flexbox",{staticClass:"reminder-body",attrs:{align:"stretch"}},[i("i",{staticClass:"wk wk-warning reminder-icon"}),t._v(" "),i("div",{staticClass:"reminder-content",style:{"font-size":t.fontSize+"px"},domProps:{innerHTML:t._s(t.content)}}),t._v(" "),t._t("default"),t._v(" "),t.closeShow?i("i",{staticClass:"el-icon-close close",on:{click:t.close}}):t._e()],2)],1)},[],!1,null,"36522fcc",null);o.options.__file="Reminder.vue";e.a=o.exports},n9M2:function(t,e,i){}}]);