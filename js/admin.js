Curriculum_Data.color = {
	hex: Curriculum_Data.default_color.hex,
	hsl: {
		h: 0,
		s: 0,
		l: 0,
		a: 1
	},
	hsv: {
		h: 0,
		s: 0,
		v: 0,
		a: 1
	},
	rgba: {
		r: 0,
		g: 0,
		b: 0,
		a: 1
	},
	a: 1
};

var colorPicker = VueColor.Chrome;

var getTextColor = function( hexcolor ){
	if ( ! hexcolor )
		return '';
	hexcolor = hexcolor.indexOf('#') !== -1 ? hexcolor.replace('#', '') : hexcolor;
	var r = parseInt(hexcolor.substr(0,2),16);
	var g = parseInt(hexcolor.substr(2,2),16);
	var b = parseInt(hexcolor.substr(4,2),16);
	var yiq = ((r*299)+(g*587)+(b*114))/1000;
	return (yiq >= 128) ? 'black' : 'white';
};

var getUniqueId = function( siblings ){
	var existingIds = _.pluck( siblings, 'id' );
	existingIds.push( 0 );
	return Math.max.apply( null, existingIds ) + 1;
};

var Curriculum_UI = new Vue({
	el: '#curriculum',
	data: typeof Curriculum_Data !== 'undefined' ? Curriculum_Data : null,
	components: {
		'color-picker': colorPicker
	},
	methods: {
		__return_false: function( event ){
			event.preventDefault();
		},
		__save_undo_data: function( message ){
			if ( ! window.sessionStorage )
				return false;
			sessionStorage.setItem( 'curriculum_groups', JSON.stringify( Curriculum_Data.groups ) );
			sessionStorage.setItem( 'curriculum_modules', JSON.stringify( Curriculum_Data.modules) );
			this.undo.enable = true;
			this.undo.message = message;
		},
		__restore_undo_data: function(){
			var groups = JSON.parse( sessionStorage.getItem('curriculum_groups') );
			var modules = JSON.parse( sessionStorage.getItem('curriculum_modules') );
			this.$data.groups = groups;
			this.$data.modules = modules;
			this.undo.enable = false;
		},
		group__textcolor: function( color ){
			return getTextColor( color );
		},
		group__create: function(){
			this.new_group.is_creating = true;
			this.new_group.name = '';
			this.new_group.id = null;
		},
		group__create_color: function( color ){
			this.new_group.color = color.hex;
		},
		group__create_add: function( ){
			if ( this.new_group.name == '' )
				return;
			if ( this.new_group.id ) {
				// editando
				var new_group = this.new_group;
				_.forEach( this.groups, function( group ){
					if ( group.id == new_group.id ) {
						group.name = new_group.name;
						group.color = new_group.color;
					}
				});
			} else {
				// creando
				this.groups.push({
					id: getUniqueId( this.groups ),
					name: this.new_group.name,
					color: this.new_group.color
				});
			}
			this.new_group.is_editing = false;
			this.new_group.is_creating = false;
			this.new_group.id = null;
			this.new_group.name = '';
			this.color.hex = this.default_color.hex;
			this.undo.enable = false;
		},
		group__create_cancel: function(){
			this.new_group.is_creating = false;
			this.new_group.is_editing = false;
		},
		group__delete: function( index ){
			this.__save_undo_data( 'Los ramos asignados a este grupo serán reasignados. ¿Deseas deshacer este cambio?' );
			var deletedGroup  = this.groups[ index ];
			this.groups.splice( index, 1 );
			var fallbackGroup = _.first( this.groups );
			_.forEach(this.modules, function( module ) {
				_.forEach( module.subjects, function( subject ){
					if ( subject.group == deletedGroup.id ) {
						subject.group = fallbackGroup.id;
					}
				});
			});
		},
		group__edit: function( group ){
			this.group__create_cancel();
			var new_group = this.new_group;
			var color = this.color;
			// nasty workaround for updating the color model
			Vue.nextTick(function(){
				new_group.is_editing = true;
				new_group.name = group.name;
				new_group.id = group.id;
				new_group.color = group.color;
				new_group.color = color.hex;
				color.hex = group.color;
			});
		},
		subject__get_group_color: function( subject ){
			var group = _.find( this.groups, function( group ){
				return group.id == subject.group;
			});
			return typeof group !== 'undefined' ? group.color : '';
		},
		subject__create: function( group_index ){
			this.new_subject.is_creating = true;
			this.new_subject.module = group_index;
			// reset values
			this.new_subject.title = '';
			this.new_subject.description = '';
			this.new_subject.credits = '';
		},
		subject__create_cancel: function( ){
			this.new_subject.is_creating = false;
		},
		subject__create_add: function( group_index ){
			if ( this.new_subject.title == '' )
				return;
			this.modules[ group_index ].subjects.push({
				id: getUniqueId( this.modules[ group_index ].subjects ),
				title: this.new_subject.title,
				group: this.new_subject.group,
				description: this.new_subject.description,
				credits: this.new_subject.credits
			});
			this.new_subject.is_creating = false;
			this.new_subject.title = '';
			this.new_subject.description = '';
			this.new_subject.creditst = '';
			this.subject__create_cancel();
			var $this = this;
			Vue.nextTick(function(){
				$this.subject__create( group_index );
			});
			this.undo.enable = false;
		},
		subject__delete: function( module_index, subject_index ){
			this.__save_undo_data('El ramo ha sido eliminado correctamente. ¿Deseas deshacer este cambio?');
			this.modules[ module_index ].subjects.splice( subject_index, 1 );
		},
		subject__edit: function( module_index, subject_id ){
			var editedSubject = _.find(this.modules[ module_index ].subjects, function( subject ){
				return subject.id == subject_id;
			});
			this.new_subject.is_editing = true;
			this.new_subject.module = module_index;
			this.new_subject.id = subject_id;
			this.new_subject.title = editedSubject.title;
			this.new_subject.description = editedSubject.description;
			this.new_subject.group = editedSubject.group;
			this.new_subject.credits = editedSubject.credits;
		},
		subject__edit_save: function( module_index, subject_index ){
			if ( this.new_subject == '' )
				return;
			this.new_subject.is_editing = false;
			this.modules[ module_index ].subjects[ subject_index ].title = this.new_subject.title;
			this.modules[ module_index ].subjects[ subject_index ].description = this.new_subject.description;
			this.modules[ module_index ].subjects[ subject_index ].group = this.new_subject.group;
			this.modules[ module_index ].subjects[ subject_index ].credits = this.new_subject.credits;
			this.new_subject.title = '';
			this.new_subject.description = '';
			this.new_subject.credits = '';
			this.undo.enable = false;
		},
		subject__edit_cancel: function(){
			this.new_subject.is_editing = false;
		},
		module__edit: function( module ){
			this.new_module.is_editing = true;
			this.new_module.id = module.id;
			this.new_module.title = module.title;
		},
		module__edit_save: function(){
			var updatedModule = this.new_module;
			_.forEach( this.modules, function( mod ){
				if ( mod.id == updatedModule.id ) {
					mod.title = updatedModule.title;
				}
			});
			this.new_module.is_editing = false;
			this.new_module.title = '';
			this.undo.enable = false;
		},
		module__create: function(){
			this.modules.push({
				id: getUniqueId( this.modules ) ,
				title: this.modules.length + 1 +' Semestre',
				subjects: []
			});
			this.undo.enable = false;
		},
		module__delete: function( index ){
			this.__save_undo_data('El módulo y todos sus ramos han sido eliminados. ¿Deseas deshacer este cambio?');
			this.modules.splice( index, 1 );
		}
	}
});

Vue.directive('focus', {
	inserted: function( el ){
		el.focus();
	}
});

jQuery(document).ready(function($){
	/**
	 * Inyectar valores de grupos y módulos en elementos de formulario de la página
	 * para guardar en base de datos
	 */
	$('#post').on('submit', function(event){
		$('#curriculum-data__groups').val( JSON.stringify( Curriculum_Data.groups ) );
		$('#curriculum-data__modules').val( JSON.stringify( Curriculum_Data.modules ) );
		return true;
	});
});