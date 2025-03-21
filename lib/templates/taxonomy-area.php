<!-- wp:template-part {"slug":"header","theme":"ucsc-2022","tagName":"header","className":"header-region"} /-->

<!-- wp:template-part {"slug":"breadcrumbs","theme":"ucsc-2022","tagName":"section","className":"breadcrumbs-region"} /-->

<!-- wp:group {"tagName":"main","className":"content-region","style":{"spacing":{"margin":{"top":"var:preset|spacing|50"}}},"layout":{"inherit":true,"type":"constrained"}} -->
<main class="wp-block-group content-region" style="margin-top:var(--wp--preset--spacing--50)"><!-- wp:query-title {"type":"archive","textAlign":"center","showPrefix":false} /-->
<!-- wp:paragraph -->
		<p><?php esc_html_e( 'This is a plugin-registered template.', 'ucscgiving' ); ?></p>
		<!-- /wp:paragraph -->

<?php require 'parts/post-query-funds.php'; ?>
</main>
<!-- /wp:group -->

<!-- wp:template-part {"slug":"footer","theme":"ucsc-2022","tagName":"footer","className":"footer-region"} /-->