<div class="malla-curricular">
	<nav class="malla-nav">
		<ul class="pagination">
			<li><span>Semestres:</span></li>
			<?php $i = 0; foreach ( $curriculum->get_modules() as $module ) : ?>
			<li><a href="#semestre-<?php echo $i ; ?>" class="page-number"><?php echo $module->id ; ?></a></li>
			<?php ++$i; endforeach; ?>
			<?php if ( ! empty( $download ) ) : ?>
			<li><a href="<?php echo wp_get_attachment_url( $download->ID ); ?>" class="download-link">Descargar Malla <svg class="svg-download-dims"><use xlink:href="#download"></use></svg></a></li>
			<?php endif; ?>
		</ul>
	</nav>
	<?php if ( ! empty( $atts['disclaimer'] ) ) : ?>
	<div class="course-schedule-note">
		<?php echo wptexturize( urldecode( $atts['disclaimer'] ) ); ?>
	</div>
		<?php endif; ?>
	<table class="table table-course-schedule">
		<tbody>
			<?php foreach ( $curriculum->get_modules() as $module ) : ?>
			<tr id="semestre-1">
				<td class="col-md-3 semester">
					<h4><?php echo $module->title; ?></h4>
				</td>
				<td class="col-md-9">
					<ul class="course-list">
						<?php $si = 0; foreach ( $module->subjects as $subject ) : ?>
						<li class="course">
							<header class="course__header">
								<?php if ( ! empty( $subject->description ) ) : ?>
								<a href="#course-<?php echo "{$i}--{$si}"; ?>" class="course__name" data-toggle="collapse" role="button" aria-expanded="false" aria-controls="course-<?php echo "{$i}--{$si}"; ?>">
									<svg class="svg-arrow-dims"><use xlink:href="#arrow"></use></svg>&nbsp;
									<?php echo $subject->title; ?>
								</a>
								<?php else : ?>
								<span class="course__name"><svg class="svg-arrow-dims"><use xlink:href="#arrow"></use></svg> <?php echo $subject->title ?></span>
								<?php endif; ?>
								<?php if ( $subject->credits ) : ?>
								<span class="course__credits"><?php echo $subject->credits; ?> créditos</span>
								<?php endif; ?>
							</header>
							<?php if ( ! empty( $subject->description ) ) : ?>
							<div id="course-<?php echo "{$i}--{$si}"; ?>" class="course__description collapse">
								<?php echo wptexturize( $subject->description ); ?>
							</div>
							<?php endif; ?>
						</li>
						<?php ++$si; endforeach; ?>
					</ul>
				</td>
			</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</div>