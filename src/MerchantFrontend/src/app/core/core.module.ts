import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { LayoutsModule } from './layouts/layouts.module';
import { MerchantApiService } from './services/merchant-api.service';
import { HttpClientModule } from '@angular/common/http';

@NgModule({
  imports: [
    CommonModule,
    LayoutsModule,
    HttpClientModule
  ],
  declarations: [],
  exports: [
    LayoutsModule
  ],
  providers: [
    MerchantApiService
  ]
})
export class CoreModule {}
