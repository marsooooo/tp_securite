Failles critiques trouvées

1. Injections SQL
   Fichiers : login.php, messages.php, product.php, profile.php, search.php, sell.php, admin.php, api.php
   Probleme : les variables utilisateur sont concatenees directement dans les requetes SQL.

2. Mot de passe MD5 (init.php, login.php, register.php, profile.php)
   Probleme : MD5 n'est pas un algorithme de hachage de mot de passe.

3. XSS Stocke (messages.php, product.php, profile.php, admin.php)
   Probleme : contenu saisi par un utilisateur (message, avis, bio) reaffiche sans encodage.

4. XSS Reflechi (search.php)
   Probleme : le terme de recherche $q est reaffiche brut dans le HTML.

5. CSRF (tous les formulaires POST)
   Probleme : aucun token anti-CSRF sur les formulaires.

6. Controle d'acces cassé (admin.php)
   Probleme : le check de role affiche une erreur mais le code continue de s'executer.

7. API sans auth (api.php)
   Probleme :
   - action=users : exporte tous les utilisateurs (emails, hashes de mdp) sans etre connecte
   - action=transfer : transfère de l'argent entre comptes sans aucune verification
   - action=raw_query : execute n'importe quelle requete SQL arbitraire

8. INJECTION ORDER BY (search.php)
   Probleme : $sort est injecte directement dans ORDER BY sans validation.
     ORDER BY $sort ASC
     => permet d'extraire des donnees via blind SQL injection
   Correction : whitelist explicite des colonnes autorisees ['name', 'price'].

9. UPLOAD DE FICHIER SANS VALIDATION - RCE (sell.php)
   Probleme : aucune validation du type de fichier uploade.
     $filename = $_FILES['image']['name'];
     move_uploaded_file($tmp, '/uploads/' . $filename);
     => uploader shell.php => execution de commandes systeme via /uploads/shell.php?cmd=whoami
   Correction : validation du type MIME + whitelist des extensions + nom de fichier aleatoire.

10. IDOR - ACCES DIRECT AUX OBJETS (sell.php)
    Probleme : pas de verification que le produit appartient a l'utilisateur.
      DELETE FROM products WHERE id=$pid
      => n'importe quel utilisateur connecte peut supprimer ou modifier le prix d'un produit d'un autre
    Correction : ajout de AND seller_id=? dans les requetes DELETE et UPDATE.

11. EXPOSITION DU HASH DE MOT DE PASSE (admin.php)
    Probleme : la colonne "Mot de passe (MD5)" est affichee dans le tableau admin.
    Correction : colonne supprimee de l'affichage.

12. BRUTE FORCE LOGIN (login.php)
    Probleme : aucune limite sur les tentatives de connexion.
    Un attaquant peut tester des milliers de mots de passe automatiquement.
    Correction : check_rate_limit() dans init.php, max 10 tentatives par 5 minutes par IP.

SYNTHESE OWASP TOP 10
---------------------
A01 Broken Access Control     : admin.php (pas de exit), api.php (pas d'auth), sell.php (IDOR) => CORRIGE
A02 Cryptographic Failures    : MD5 partout, hash expose dans admin => CORRIGE
A03 Injection                 : SQL injection + XSS dans tous les fichiers => CORRIGE
A04 Insecure Design           : raw_query API, credentials affiches dans login.php => CORRIGE
A07 Auth & Session Failures   : MD5, brute force login => CORRIGE
