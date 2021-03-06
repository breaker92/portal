import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';

import { MerchantProductsRoutingModule } from './merchant-products-routing.module';
import { MerchantProductsListingComponent } from './merchant-products-listing/merchant-products-listing.component';
import { MerchantProductsDetailComponent } from './merchant-products-detail/merchant-products-detail.component';
import {ClrDatagridModule, ClrFormsModule, ClrIconModule} from '@clr/angular';


@NgModule({
  declarations: [MerchantProductsListingComponent, MerchantProductsDetailComponent],
  imports: [
    CommonModule,
    MerchantProductsRoutingModule,

    // Clarity
    ClrDatagridModule,
    ClrIconModule,
    ClrFormsModule
  ]
})
export class MerchantProductsModule { }
