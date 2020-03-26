import { APP_INITIALIZER, NgModule } from '@angular/core';
import {CommonModule, Location } from '@angular/common';
import {LayoutsModule} from './layouts/layouts.module';
import { HttpClientModule } from '@angular/common/http';
import { LoginService } from './services/login.service';
import { onAppInit } from './app-init';
import { LocalStorageService } from './services/local-storage.service';

@NgModule({
  imports: [
    CommonModule,
    HttpClientModule,
    LayoutsModule
  ],
  declarations: [],
  exports: [
    LayoutsModule
  ],
  providers: [
    {
      provide: APP_INITIALIZER,
      useFactory: onAppInit,
      multi: true,
      deps: [Location, LoginService, LocalStorageService]
    }
  ]
})
export class CoreModule {
}
