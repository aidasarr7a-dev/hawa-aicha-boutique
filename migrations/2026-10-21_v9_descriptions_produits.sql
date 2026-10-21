-- Hawa Aïcha Boutique — migration v9 : descriptions détaillées des 14 produits de démonstration
-- Ne remplace une description que si elle est encore celle d'origine (une description modifiée
-- dans l'administration est conservée). Peut être relancée sans risque.

SET NAMES utf8mb4;
USE hawa_aicha;

UPDATE produits SET description = 'Parfum corporel à la senteur douce et durable, pour se sentir fraîche toute la journée. Quelques pulvérisations sur le cou et les poignets suffisent. Il convient aussi bien pour le travail que pour les sorties.'
 WHERE nom = 'Parfum corporel' AND description = 'Parfum pour une utilisation quotidienne.';

UPDATE produits SET description = 'Parfum d''ambiance qui laisse une senteur agréable et chaleureuse dans le salon, la chambre ou l''entrée. Quelques pulvérisations suffisent pour accueillir vos invités dans une maison qui sent bon. Son parfum dure plusieurs heures.'
 WHERE nom = 'Parfum d''intérieur' AND description = 'Parfum destiné à apporter une senteur agréable à la maison.';

UPDATE produits SET description = 'Crème qui protège la peau du soleil et de la chaleur au quotidien. Sa texture légère pénètre vite, sans laisser de traces blanches. À appliquer le matin sur le visage et le corps, puis à renouveler après une longue exposition.'
 WHERE nom = 'Crème solaire' AND description = 'Produit de protection solaire pour les soins de la peau.';

UPDATE produits SET description = 'Sac à main élégant et pratique, assez grand pour le téléphone, le portefeuille et les petits essentiels de la journée. Ses anses solides se portent à la main ou au creux du bras. Il accompagne aussi bien une tenue de bureau qu''une tenue de sortie.'
 WHERE nom = 'Sac à main féminin' AND description = 'Sac élégant pour sorties et usage quotidien.';

UPDATE produits SET description = 'Voile léger et doux, agréable à porter même par temps chaud. Son tissu fluide se drape facilement et tient bien toute la journée. Il se marie avec toutes vos tenues, pour le quotidien comme pour les grandes occasions.'
 WHERE nom = 'Voile pour femme' AND description = 'Voile élégant pour compléter différentes tenues.';

UPDATE produits SET description = 'Jalabé ample et confortable, au tissu fluide qui laisse respirer la peau. Sa coupe élégante convient pour la maison, la prière, les visites ou les cérémonies. Une tenue traditionnelle facile à porter et à entretenir.'
 WHERE nom = 'Jalabé' AND description = 'Tenue traditionnelle élégante et confortable.';

UPDATE produits SET description = 'Encens traditionnel à brûler, pour parfumer la maison d''une senteur chaude et persistante. Il suffit d''en déposer un peu sur le charbon de l''encensoir. Idéal pour accueillir les invités ou parfumer le linge.'
 WHERE nom = 'Encens traditionnel' AND description = 'Encens à brûler pour parfumer agréablement la maison.';

UPDATE produits SET description = 'Huile qui nourrit et adoucit la peau en profondeur, sans laisser de film gras. Elle s''utilise sur le corps, les mains et les pointes des cheveux. Appliquez-la après la douche, sur une peau encore légèrement humide, pour un meilleur résultat.'
 WHERE nom = 'Huile de soin' AND description = 'Huile pour le soin quotidien de la peau.';

UPDATE produits SET description = 'Lait hydratant pour le corps, à la texture fluide qui pénètre rapidement. Il laisse la peau douce, souple et délicatement parfumée toute la journée. À appliquer chaque jour après la douche.'
 WHERE nom = 'Lait corporel' AND description = 'Lait pour le soin quotidien du corps.';

UPDATE produits SET description = 'Lunettes de soleil au style rétro, qui protègent les yeux de la lumière et donnent une touche chic à votre tenue. Leur monture légère se porte confortablement toute la journée.'
 WHERE nom = 'Lunettes de soleil' AND description = 'Lunettes de soleil au style rétro.';

UPDATE produits SET description = 'Parure fantaisie de couleur dorée, avec un collier et des bracelets assortis. Elle illumine une tenue simple comme une tenue de cérémonie : mariage, baptême ou fête. Légère et agréable à porter.'
 WHERE nom = 'Bijoux fantaisie dorés' AND description = 'Collier et bracelets fantaisie de couleur dorée.';

UPDATE produits SET description = 'Petite pochette élégante pour les soirées, les mariages et les cérémonies. Elle contient l''essentiel : téléphone, clés, rouge à lèvres et un peu de monnaie. Elle se tient à la main pour une allure raffinée.'
 WHERE nom = 'Pochette de soirée' AND description = 'Petite pochette élégante pour les sorties et les cérémonies.';

UPDATE produits SET description = 'Sandales pour femme légères et confortables, faciles à enfiler. Leur semelle souple permet de marcher toute la journée sans fatigue. Elles vont avec les tenues de tous les jours comme avec les tenues traditionnelles.'
 WHERE nom = 'Sandales pour femme' AND description = 'Sandales confortables pour tous les jours.';

UPDATE produits SET description = 'Coussins décoratifs moelleux, pour apporter du confort et une touche chaleureuse au salon ou à la chambre. Ils s''accordent facilement avec un canapé, un lit ou des fauteuils. Une façon simple de renouveler la décoration de la maison.'
 WHERE nom = 'Coussins décoratifs' AND description = 'Coussins pour décorer et apporter du confort au salon ou à la chambre.';
