# Event Integration Progress

## ✅ Completed - PHP Files

### Entities
- [x] Entity: Activite.php
- [x] Entity: DetailEvenement.php  
- [x] Entity: ListeAttente.php

### Controllers
- [x] EvenementController.php (RH management - `/rh/evenements`)
- [x] FrontEvenementController.php (Employee interface - `/evenements`)

### Services
- [x] EmailService.php
- [x] ShadowUserService.php

### Forms
- [x] EvenementType.php
- [x] ParticipationEvenementType.php
- [x] ActiviteType.php

## 🔄 Still Need to Copy

### Repositories
- [ ] ActiviteRepository.php
- [ ] DetailEvenementRepository.php
- [ ] EvenementRepository.php (update existing)
- [ ] ListeAttenteRepository.php
- [ ] ParticipationEvenementRepository.php (update existing)

### Templates - RH Interface
- [ ] navbarRH/evenement/index.html.twig
- [ ] navbarRH/evenement/new.html.twig
- [ ] navbarRH/evenement/edit.html.twig
- [ ] navbarRH/evenement/show.html.twig
- [ ] navbarRH/evenement/inscriptions.html.twig

### Templates - Employee Interface
- [ ] Topnavbar/evenement/index.html.twig
- [ ] Topnavbar/evenement/show.html.twig
- [ ] Topnavbar/evenement/participation_details.html.twig
- [ ] Topnavbar/evenement/participation_pdf.html.twig

### Email Templates
- [ ] emails/confirmed.html.twig
- [ ] emails/waitlist.html.twig
- [ ] emails/promotion.html.twig
- [ ] emails/layout.html.twig

### Routes
- [ ] Update config/routes.yaml

### Assets
- [ ] CSS for events (if needed)
- [ ] JS for events (if needed)

## 📝 Notes
- Need to adapt routes to match HROne structure:
  - RH: `/rh/evenements/*`
  - Employee: `/evenements/*`
- Need to update Evenement entity to include DetailEvenement relationship
- Need to update ParticipationEvenement entity
