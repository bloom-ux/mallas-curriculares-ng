<div id="curriculum">
	<div class="notice notice-info" v-if="undo.enable">
		<p><button type="button" class="button--link" v-on:click="__restore_undo_data">{{ undo.message }}</button></p>
	</div>
	<h2>Grupos de ramos</h2>
	<p class="description">Define las agrupaciones que aplican a los ramos de la malla.</p>
	<draggable class="curriculum-groups" :list="groups">
		<div class="curriculum-group" v-for="(group, group_index) in groups">
			<span class="curriculum-group__title" v-bind:style="{ backgroundColor: group.color, color: group__textcolor( group.color ) }" v-bind:class="{ 'curriculum-group__title--editing': new_group.is_editing && new_group.id == group.id }">
				{{ group.name }}
				<span class="dashicons dashicons-edit curriculum-group__edit" v-on:click="group__edit( group )" title="Editar este grupo"></span>
				<span class="dashicons dashicons-trash curriculum-group__delete" v-on:click="group__delete( group_index )" title="Eliminar este grupo"></span>
			</span>
		</div>
		<div v-if="! new_group.is_creating && ! new_group.is_editing ">
			<button class="button--link" v-on:click="group__create" type="button">Crear nuevo grupo</button>
		</div>
	</draggable>
	<div class="curriculum-group__create" v-if="new_group.is_creating || new_group.is_editing ">
		<div class="new-group__field">
			<label for="new-group__name-field">Nombre del grupo</label>
			<input id="new-group__name-field" type="text" class="regular-text" v-model="new_group.name" v-on:keydown.enter="__return_false" v-focus>
		</div>
		<div class="new-group__field">
			<color-picker v-model="color" @change-color="group__create_color">
			</color-picker>
		</div>
		<div class="new-group__actions">
			<button class="button-primary" type="button" v-on:click="group__create_add">{{ new_group.is_creating ? 'Agregar' : 'Editar' }}</button>
			<button class="button" type="button" v-on:click="group__create_cancel">Cancelar</button>
		</div>
		</div>
	<div class="curriculum-modules">
		<p class="description">Añade los módulos (semestres) en que se organiza la malla. Después de crear un ramo, puedes editarlo para ingresar su descripción.</p><br>
		<div class="curriculum-modules__viewport">
			<div class="curriculum-module" v-for="(module, module_index) in modules">
				<div v-if="new_module.is_editing && new_module.id == module.id" class="curriculum-module__title curriculum__title--editing">
					<div class="curriculum-module__field">
						<input type="text" name="title" class="widefat" v-model="new_module.title" v-focus v-on:keydown.enter="__return_false">
					</div>
					<button class="button button-primary button-small curriculum-module__edit-button" type="button" v-on:click="module__edit_save">Ok</button>
				</div>
				<div v-else class="curriculum-module__title">
					{{ module.title }}
					<span class="dashicons dashicons-edit curriculum-module__edit" v-on:click="module__edit( module )" title="Editar este módulo"></span>
					<span class="dashicons dashicons-trash curriculum-module__delete action--danger" v-on:click="module__delete( module_index )" title="Eliminar este módulo"></span>
				</div>
				<div class="curriculum-module__subjects">
					<draggable :list="module.subjects" :options="{group:'subjects'}">
						<div class="curriculum-subject" v-for="(subject, subject_index) in module.subjects">
							<div class="curriculum-subject__title-bar">
								<div class="curriculum-subject__title-bar-actions">
									<span class="curriculum-subject__group" v-bind:style="{ backgroundColor: subject__get_group_color( subject ) }"></span>
									<span class="dashicons dashicons-trash curriculum-subject__delete action--danger" v-on:click="subject__delete( module_index, subject_index )"></span>
								</div>
								<div class="curriculum-subject__title">
									{{ subject.title }}
								</div>
								<div class="curriculum-subject__edit">
									<button class="dashicons dashicons-edit button-link" type="button" v-on:click="subject__edit( module_index, subject.id )"></button>
								</div>
							</div>
							<div v-if="new_subject.is_editing && new_subject.module == module_index && new_subject.id == subject.id " class="curriculum-subject__edit-form">
								<div class="curriculum-subject__field">
									<label for="curriculum-subject__title-field">Título</label>
									<input type="text" name="title" id="curriculum-subject__title-field" class="widefat" v-model="new_subject.title" v-on:keydown.enter="__return_false" v-focus>
								</div>
								<div class="curriculum-subject__field">
									<label for="curriculum-subject__group-field">Grupo</label>
									<select name="group" id="curriculum-subject__group-field" class="widefat" v-model="new_subject.group">
										<option v-for="group in groups" v-bind:value="group.id">{{ group.name }}</option>
									</select>
								</div>
								<div class="curriculum-subject__field">
									<label for="curriculum-subject__description-field">Descripción</label>
									<textarea name="description" id="curriculum-subject__description-field" cols="30" rows="5" class="widefat" v-model:value="new_subject.description"></textarea>
								</div>
								<div class="curriculum-subject__field">
									<label for="curriculum-subject__credits-field">Créditos</label>
									<input type="number" name="credits" id="curriculum-subject__credits-field" class="small-text" v-model="new_subject.credits">
								</div>
								<div class="curriculum-subject__actions">
									<button type="button" class="button button-primary" v-on:click="subject__edit_save( module_index, subject_index )">Guardar</button>
									<button type="button" class="button" v-on:click="subject__edit_cancel">Cancelar</button>
									<button type="button" class="button curriculum-subject__delete" v-on:click="subject__delete( module_index, subject_index )"><span class="dashicons dashicons-trash action--danger"></span></button>
								</div>
							</div>
						</div>
					</draggable>
					<div class="curriculum-subject__create">
						<div class="new-curriculum-subject" v-if="new_subject.is_creating && new_subject.module == module_index">
							<div class="new-curriculum-subject__title new-curriculum-subject__field curriculum-subject__field">
								<label for="new-curriculum-subject__title-field">Título</label>
								<input v-focus type="text" name="title" id="new-curriculum-subject__title-field" class="widefat" v-model="new_subject.title" v-on:keydown.enter="__return_false">
							</div>
							<div class="new-curriculum-subject__group new-curriculum-subject__field curriculum-subject__field">
								<label for="new-curriculum-subject__group-field">Grupo</label>
								<select name="group" id="new-curriculum-subject__group-field" class="widefat" v-model="new_subject.group">
									<option v-for="group in groups" v-bind:value="group.id">{{ group.name }}</option>
								</select>
							</div>
							<div class="curriculum-subject__field">
								<label for="curriculum-subject__description-field">Descripción</label>
								<textarea name="description" id="curriculum-subject__description-field" cols="30" rows="5" class="widefat" v-model:value="new_subject.description"></textarea>
							</div>
							<div class="curriculum-subject__field">
								<label for="curriculum-subject__credits-field">Créditos</label>
								<input type="number" name="credits" id="curriculum-subject__credits-field" class="small-text" v-model="new_subject.credits">
							</div>
							<div class="new-curriculum-subject__actions curriculum-subject__actions">
								<button type="button" class="button button-primary" v-on:click="subject__create_add( module_index )">Añadir</button>
								<button type="button" class="button" v-on:click="subject__create_cancel">Cancelar</button>
							</div>
						</div>
						<div v-else>
							<button type="button" class="button--link new-curriculum-subject__trigger" v-on:click="subject__create( module_index )">Nuevo ramo</button>
						</div>
					</div>
				</div>
			</div>
			<div class="curriculum-module curriculum-module__create">
				<button type="button" clas="button--link" v-on:click="module__create">Nuevo módulo <span class="dashicons dashicons-plus"></span></button>
			</div>
		</div>
	</div>
	<input type="hidden" name="groups" id="curriculum-data__groups" value="">
	<input type="hidden" name="modules" id="curriculum-data__modules" value="">
</div>